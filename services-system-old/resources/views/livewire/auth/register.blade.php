<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
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
            'is_of_age'   => ['boolean'],
        ]);

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

        $this->redirect($redirect, navigate: true);
    }
}; ?>

<div class="flex flex-col gap-5">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            Create an account
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            Enter your details below to create your account
        </p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-4">

        {{-- Name --}}
        <div class="flex flex-col gap-1.5">
            <label for="name" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Full name</label>
            <flux:input
                wire:model="name"
                id="name"
                type="text"
                name="name"
                required
                autofocus
                autocomplete="name"
                placeholder="Full name"
            />
        </div>

        {{-- Username --}}
        <div class="flex flex-col gap-1.5">
            <label for="username" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Username</label>
            <flux:input
                wire:model="username"
                id="username"
                type="text"
                name="username"
                required
                autocomplete="username"
                placeholder="Username"
            />
        </div>

        {{-- Email --}}
        <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Email address</label>
            <flux:input
                wire:model="email"
                id="email"
                type="email"
                name="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />
        </div>

        {{-- Divider --}}
        <div class="border-t border-zinc-100 dark:border-zinc-800 my-0.5"></div>

        {{-- Password --}}
        <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Password</label>
            <flux:input
                wire:model="password"
                id="password"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Password"
                viewable
            />
        </div>

        {{-- Confirm Password --}}
        <div class="flex flex-col gap-1.5">
            <label for="password_confirmation" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Confirm password</label>
            <flux:input
                wire:model="password_confirmation"
                id="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Confirm password"
                viewable
            />
        </div>

        {{-- Divider --}}
        <div class="border-t border-zinc-100 dark:border-zinc-800 my-0.5"></div>

        {{-- Confirmations --}}
        <div class="flex flex-col gap-3 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 px-4 py-3.5">

            {{-- Resident --}}
            <div class="flex items-start gap-3">
                <flux:checkbox
                    wire:model="is_resident"
                    id="is_resident"
                    name="is_resident"
                    class="mt-0.5"
                />
                <label for="is_resident" class="text-sm text-zinc-600 dark:text-zinc-400 leading-snug cursor-pointer">
                    I confirm that I am a <span class="font-medium text-zinc-800 dark:text-zinc-200">resident of Rosario</span>
                </label>
            </div>

            {{-- Age --}}
            <div class="flex items-start gap-3">
                <flux:checkbox
                    wire:model="is_of_age"
                    id="is_of_age"
                    name="is_of_age"
                    class="mt-0.5"
                />
                <label for="is_of_age" class="text-sm text-zinc-600 dark:text-zinc-400 leading-snug cursor-pointer">
                    I confirm that I am <span class="font-medium text-zinc-800 dark:text-zinc-200">18 years of age or older</span>
                </label>
            </div>

        </div>

        <flux:button type="submit" variant="primary" class="w-full mt-1">
            {{ __('Create account') }}
        </flux:button>

    </form>

    <p class="text-center text-sm text-zinc-500 dark:text-zinc-400">
        Already have an account?
        <x-text-link href="{{ route('login') }}">Log in</x-text-link>
    </p>

</div>