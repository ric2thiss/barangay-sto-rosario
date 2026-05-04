<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    #[Validate('required|string')]
    public string $username = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     *
     * Flow:
     *  1. Check profiling-system → barangay_official (Secretary/Admin)
     *  2. Check profiling-system → residents (Resident)
     *  3. Fallback: check local services-system → users table
     */
    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $authenticated = false;

        // ═══════════════════════════════════════════════════════════
        // STEP 1: Check profiling-system → barangay_official table
        // ═══════════════════════════════════════════════════════════
        $official = DB::connection('sto_rosario')
            ->table('barangay_official')
            ->where('username', $this->username)
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->first();

        if ($official && $this->verifyPassword($this->password, $official->password)) {
            // Check if the official account has a matching local user
            $user = \App\Models\User::where('username', $this->username)->first();

            if (!$user) {
                // Auto-create a local user for the official
                $roleName = $this->getOfficialRole($official->position ?? '');
                $role = \App\Models\Role::where('role_name', $roleName)->first();

                $user = \App\Models\User::create([
                    'name'     => trim(($official->first_name ?? '') . ' ' . ($official->surname ?? '')),
                    'username' => $this->username,
                    'email'    => $this->username . '@services.local',
                    'password' => Hash::make($this->password),
                    'role_id'  => $role?->role_id,
                    'status'   => 'active',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            } else {
                // Ensure the role is correct (Secretary/Admin)
                $roleName = $this->getOfficialRole($official->position ?? '');
                $role = \App\Models\Role::where('role_name', $roleName)->first();
                if ($role && $user->role_id !== $role->role_id) {
                    $user->update(['role_id' => $role->role_id, 'status' => 'active']);
                }
            }

            Auth::login($user, $this->remember);
            $authenticated = true;
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 2: Check profiling-system → residents table
        // ═══════════════════════════════════════════════════════════
        if (!$authenticated) {
            $resident = DB::connection('sto_rosario')
                ->table('residents')
                ->where('username', $this->username)
                ->where('is_deleted', 0)
                ->whereNotNull('username')
                ->where('username', '!=', '')
                ->first();

            if ($resident && $this->verifyPassword($this->password, $resident->password)) {
                // Check account status
                if (($resident->account_status ?? 'active') === 'suspended') {
                    throw ValidationException::withMessages([
                        'username' => 'Your account has been suspended. Please contact the barangay office.',
                    ]);
                }

                if (($resident->account_status ?? 'active') === 'inactive') {
                    throw ValidationException::withMessages([
                        'username' => 'Your account is inactive. Please contact the barangay office.',
                    ]);
                }

                // Check if a local user exists for this resident
                $user = \App\Models\User::where('username', $this->username)->first();

                if (!$user) {
                    // Auto-create a local user for the resident
                    $residentRole = \App\Models\Role::where('role_name', 'Resident')->first();

                    $user = \App\Models\User::create([
                        'name'        => trim(($resident->first_name ?? '') . ' ' . ($resident->surname ?? '')),
                        'username'    => $this->username,
                        'email'       => ($resident->email ?? $this->username . '@resident.local'),
                        'password'    => Hash::make($this->password),
                        'role_id'     => $residentRole?->role_id,
                        'status'      => 'active',
                        'is_resident' => true,
                    ]);
                    $user->forceFill(['email_verified_at' => now()])->save();
                } else {
                    // Ensure they have the Resident role
                    $residentRole = \App\Models\Role::where('role_name', 'Resident')->first();
                    if ($residentRole && $user->role_id !== $residentRole->role_id) {
                        $user->update(['role_id' => $residentRole->role_id]);
                    }
                }

                Auth::login($user, $this->remember);
                $authenticated = true;
            }
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 3: Fallback — check local users table (email or username)
        // ═══════════════════════════════════════════════════════════
        if (!$authenticated) {
            // Try login with username field
            $user = \App\Models\User::where('username', $this->username)
                ->orWhere('email', $this->username)
                ->first();

            if ($user && Hash::check($this->password, $user->password)) {
                Auth::login($user, $this->remember);
                $authenticated = true;
            }
        }

        // ═══════════════════════════════════════════════════════════
        // FINAL: Handle result
        // ═══════════════════════════════════════════════════════════
        if (!$authenticated) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        // Block deactivated local accounts
        $loggedInUser = Auth::user();
        if ($loggedInUser && in_array(strtolower($loggedInUser->status ?? ''), ['inactive', 'deactivated'])) {
            Auth::logout();

            throw ValidationException::withMessages([
                'username' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        // Role-based redirect
        $role = $loggedInUser->role?->role_name;

        $redirect = match ($role) {
            'Admin', 'Secretary' => route('dashboard', absolute: false),
            'Resident'           => route('certificates.resident_index', absolute: false),
            default              => route('dashboard', absolute: false),
        };

        $this->redirectIntended(default: $redirect, navigate: true);
    }

    /**
     * Verify password — supports bcrypt $2y$ and $2b$ hashes, plus plain-text fallback.
     */
    protected function verifyPassword(string $input, ?string $hash): bool
    {
        if (!$hash) {
            return false;
        }

        // Standard bcrypt verification (handles both $2y$ and $2b$)
        if (password_verify($input, $hash)) {
            return true;
        }

        // Plain-text fallback (legacy accounts)
        if ($hash === $input) {
            return true;
        }

        return false;
    }

    /**
     * Determine the appropriate role for a barangay official based on position.
     */
    protected function getOfficialRole(string $position): string
    {
        $pos = strtolower($position);

        if (str_contains($pos, 'secretary')) {
            return 'Secretary';
        }

        if (str_contains($pos, 'captain') || str_contains($pos, 'admin')) {
            return 'Admin';
        }

        // All other officials (SB Members, SK Chairman, etc.) → Secretary role
        return 'Secretary';
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->username) . '|' . request()->ip());
    }
}; ?>

{{-- ── Login Form Card (matches attendance-system exactly) ───── --}}
<div>
    <div class="text-center mb-4">
        <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo" class="mb-3 d-block mx-auto" style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
        <h4 class="fw-bold mb-1">Sign In</h4>
        <p class="text-muted small">Enter your credentials to access the Services System</p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="text-center mb-3" :status="session('status')" />

    {{-- Error display --}}
    @error('username')
        <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert" style="font-size: 0.85rem; border-radius: 10px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ $message }}</div>
        </div>
    @enderror

    <form wire:submit="login">
        {{-- Username --}}
        <div class="mb-3">
            <label class="form-label small fw-semibold">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                <input
                    type="text"
                    class="form-control"
                    wire:model="username"
                    placeholder="Enter your username"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                <input
                    type="password"
                    class="form-control"
                    wire:model="password"
                    id="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password"
                >
            </div>
        </div>

        {{-- Remember / Forgot --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="rememberMe" wire:model="remember">
                <label class="form-check-label small" for="rememberMe">Remember me</label>
            </div>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color: var(--brand-primary);">Forgot?</a>
            @endif
        </div>

        {{-- Submit --}}
        <button class="btn btn-login w-100 py-2 rounded-3" type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="login"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</span>
            <span wire:loading wire:target="login"><span class="spinner-border spinner-border-sm me-1"></span> Signing in...</span>
        </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
        Don't have an account?
        <a href="{{ route('register') }}" wire:navigate class="text-decoration-none fw-bold" style="color: var(--brand-secondary);">Register</a>
    </p>
</div>