<?php

use App\Models\User;
use App\Models\Resident;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth.simple', ['cardWidth' => 'login-card-wide'])] class extends Component {
    public string $name = '';
    public string $username = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $is_resident = false;
    public bool $is_of_age = false;

    public function register(): void
    {
        $validated = $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:' . User::class],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'is_resident' => ['boolean'],
            'is_of_age'   => ['accepted'],
        ], [
            'is_of_age.accepted' => 'You must be 18 years of age or older to register.',
        ]);

        // Resident verification logic
        if ($this->is_resident) {
            $residentExists = Resident::where(function($q) {
                $q->where(DB::raw("CONCAT_WS(' ', first_name, middle_name, surname)"), 'LIKE', "%{$this->name}%")
                  ->orWhere(DB::raw("CONCAT_WS(' ', first_name, surname)"), 'LIKE', "%{$this->name}%");
            })->exists();

            if (!$residentExists) {
                $this->addError('is_resident', 'You are not registered in the profiling system. Please contact the barangay office.');
                return;
            }
        }

        $validated['password'] = Hash::make($validated['password']);

        $residentRole = \App\Models\Role::where('role_name', 'Resident')->first();
        if ($residentRole) {
            $validated['role_id'] = $residentRole->role_id;
        }

        // is_resident and is_of_age are already in $validated, they'll be saved automatically

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $roleName = $user->role?->role_name;

        $redirect = match($roleName) {
            'Admin', 'Secretary' => route('dashboard', absolute: false),
            'Resident'           => route('certificates.resident_index', absolute: false),
            default              => route('certificates.resident_index', absolute: false),
        };

        $this->redirect($redirect);
    }
}; ?>

{{-- ── Registration Form Card (matches attendance-system style) ── --}}
<div>
    <div class="text-center mb-3">
        <img src="{{ asset('storage/logos/logo_left.jpg') }}" alt="Logo" class="mb-2 d-block mx-auto" style="width:58px;height:58px;border-radius:50%;box-shadow:0 4px 16px rgba(31,58,147,.25);">
        <h4 class="fw-bold mb-1">Create Account</h4>
        <p class="text-muted small">Join the Sto. Rosario Services System</p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="text-center mb-3" :status="session('status')" />

    <form wire:submit="register">
        {{-- Full Name --}}
        <div class="mb-3">
            <label for="name" class="form-label small fw-semibold">Full Name</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                <input
                    type="text"
                    class="form-control"
                    wire:model="name"
                    id="name"
                    placeholder="Juan Dela Cruz"
                    required
                    autofocus
                    autocomplete="name"
                >
            </div>
            @error('name') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
        </div>

        <div class="row g-2">
            {{-- Username --}}
            <div class="col-md-6 mb-3">
                <label for="username" class="form-label small fw-semibold">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-at"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        wire:model="username"
                        id="username"
                        placeholder="juandelacruz"
                        required
                        autocomplete="username"
                    >
                </div>
                @error('username') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>

            {{-- Email --}}
            <div class="col-md-6 mb-3">
                <label for="email" class="form-label small fw-semibold">Email address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                    <input
                        type="email"
                        class="form-control"
                        wire:model="email"
                        id="email"
                        placeholder="juan@example.com"
                        required
                        autocomplete="email"
                    >
                </div>
                @error('email') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="row g-2">
            {{-- Password --}}
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        wire:model="password"
                        id="password"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    >
                </div>
                @error('password') <p class="text-danger small mt-1 mb-0">{{ $message }}</p> @enderror
            </div>

            {{-- Confirm Password --}}
            <div class="col-md-6 mb-3">
                <label for="password_confirmation" class="form-label small fw-semibold">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill"></i></span>
                    <input
                        type="password"
                        class="form-control"
                        wire:model="password_confirmation"
                        id="password_confirmation"
                        placeholder="••••••••"
                        required
                        autocomplete="new-password"
                    >
                </div>
            </div>
        </div>

        {{-- Confirmations --}}
        <div class="bg-light p-2 rounded-3 border mb-2">
            <p class="small fw-semibold text-muted mb-2"><i class="bi bi-shield-check me-1"></i> Verification</p>

            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" id="is_resident" wire:model="is_resident">
                <label class="form-check-label small" for="is_resident">
                    I confirm that I am a <strong class="text-primary">resident of Sto. Rosario</strong>.
                </label>
            </div>
            @error('is_resident')
                <p class="text-danger small mb-2 ms-4">{{ $message }}</p>
            @enderror

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_of_age" wire:model="is_of_age">
                <label class="form-check-label small" for="is_of_age">
                    I confirm that I am <strong class="text-primary">18 years of age or older</strong>.
                </label>
            </div>
            @error('is_of_age')
                <p class="text-danger small mt-1 ms-4">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button class="btn btn-login w-100 py-2 rounded-3" type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="register"><i class="bi bi-person-plus me-1"></i> Create My Account</span>
            <span wire:loading wire:target="register"><span class="spinner-border spinner-border-sm me-1"></span> Setting up account...</span>
        </button>
    </form>

    <p class="text-center text-muted small mt-2 mb-0">
        Already have an account?
        <a href="{{ route('login') }}" wire:navigate class="text-decoration-none fw-bold" style="color: var(--brand-secondary);">Sign in instead</a>
    </p>
</div>