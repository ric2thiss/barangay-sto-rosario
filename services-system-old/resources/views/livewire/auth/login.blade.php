<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth.reference')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Block inactive users
        if (Auth::user()->status === 'inactive') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact an administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $role = Auth::user()->role?->role_name;

        $redirect = match($role) {
            'Resident'  => route('certificates.resident_index', absolute: false),
            default     => route('dashboard', absolute: false),
        };

        $this->redirectIntended(default: $redirect, navigate: true);
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
            'email' => __('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="login-wrap">
    <div class="login-brand">
        <div class="login-brand-circle lbc-1"></div>
        <div class="login-brand-circle lbc-2"></div>
        <div class="login-brand-content">
            <a href="../../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-75 hover-opacity-100 transition-all">
                <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
            </a>
            <div class="d-flex align-items-center gap-3 mb-4">
                <img src="{{ asset('images/logo.png') }}" onerror="this.src='https://via.placeholder.com/48'" alt="Logo" style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.15);padding:2px;">
                <div class="text-white">
                    <div class="fw-bold lh-1" style="font-size:1.1rem;">Services System</div>
                    <div class="opacity-75" style="font-size:.8rem;">Barangay E-Services</div>
                </div>
            </div>
            <h2 class="text-white fw-bold mb-2" style="font-size:clamp(1.5rem,2.5vw,2.2rem);line-height:1.25;">
                Welcome to<br><span style="color:#93c5fd;">Barangay Services</span>
            </h2>
            <p class="text-white opacity-75 mb-4" style="font-size:.9rem;max-width:350px;">
                Your centralized portal for document requests, blotter reports, and other community services.
            </p>
            <div>
                <div class="login-feature-item"><span class="login-feature-dot"></span> Request Clearances & Certificates</div>
                <div class="login-feature-item"><span class="login-feature-dot"></span> File Incident Reports</div>
                <div class="login-feature-item"><span class="login-feature-dot"></span> Manage Resident Profiles</div>
                <div class="login-feature-item"><span class="login-feature-dot"></span> Streamlined Public Service</div>
            </div>
        </div>
    </div>

    <div class="login-form-panel">
        <div class="d-md-none mb-3 w-100 text-center" style="max-width:400px;">
            <a href="../../index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none small opacity-75 hover-opacity-100 transition-all">
                <i class="bi bi-arrow-left-circle"></i> Back to Main Systems
            </a>
        </div>
        <div class="login-card">
            <div class="text-center mb-4">
                <img src="{{ asset('images/logo.png') }}" onerror="this.src='https://via.placeholder.com/64'" alt="Logo" class="mb-3" style="width:64px;height:64px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
                <h4 class="fw-bold mb-1">Sign In</h4>
                <p class="text-muted small">Enter your email and password to access</p>
            </div>

            @if (session('status'))
                <div class="alert alert-info d-flex align-items-center gap-2 py-2" role="alert" style="font-size: 0.85rem; border-radius: 10px;">
                    <i class="bi bi-info-circle-fill"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            <form wire:submit="login">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" id="email" placeholder="email@example.com" required autofocus autocomplete="email">
                    </div>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" wire:model="password" id="password" placeholder="Password" required autocomplete="current-password">
                    </div>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" wire:model="remember" id="remember">
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color: var(--brand-primary);">Forgot password?</a>
                    @endif
                </div>
                
                <button class="btn btn-login w-100 py-2 rounded-3" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</span>
                    <span wire:loading><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Signing In...</span>
                </button>
            </form>
            
            <div class="text-center mt-4">
                <p class="text-muted small mb-0">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="fw-bold text-decoration-none" style="color: var(--brand-secondary);">Sign up</a>
                </p>
            </div>
        </div>
        <div class="text-muted small mt-4" style="opacity:.6;">
            &copy; {{ date('Y') }} Services System. All rights reserved.
        </div>
    </div>
</div>