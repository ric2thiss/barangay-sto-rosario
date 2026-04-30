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

new #[Layout('components.layouts.auth')] class extends Component {
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

<div class="flex flex-col gap-5">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            Log in to your account
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Enter your email and password below to log in
        </p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-4">

        {{-- Email --}}
        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                Email address
            </label>
            <flux:input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />
        </div>

        {{-- Password --}}
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    Password
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors">
                        Forgot your password?
                    </a>
                @endif
            </div>
            <flux:input
                wire:model="password"
                id="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Password"
                viewable
            />
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center gap-2">
            <flux:checkbox wire:model="remember" id="remember" />
            <label for="remember" class="text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer select-none">
                Remember me
            </label>
        </div>

        <flux:button variant="primary" type="submit" class="w-full mt-1">
            {{ __('Log in') }}
        </flux:button>

    </form>

    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
        Don't have an account?
        <x-text-link href="{{ route('register') }}">Sign up</x-text-link>
    </p>

</div>