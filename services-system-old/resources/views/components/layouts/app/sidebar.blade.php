<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">

        <style>
            /* Sidebar base */
            [data-flux-sidebar] {
                background: linear-gradient(160deg, #1a63bd 0%, #0d69d3 55%, #1f6bb8 100%) !important;
                border-right: 1px solid rgba(255,255,255,0.08) !important;
                box-shadow: 2px 0 12px rgba(0,0,0,0.35) !important;
            }

        /* Section headings - BIG & BOLD */
/* Section headings - BIG & BOLD */
[data-flux-sidebar] div.text-xs.leading-none.text-zinc-400 {
    color: #ffffff !important;
    font-size: 0.75rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.1em !important;
    text-transform: uppercase !important;
}


            /* Nav items */
            [data-flux-sidebar] [data-flux-navlist-item] {
                color: rgba(255,255,255,0.80) !important;
                border-radius: 8px !important;
                font-size: 0.875rem !important;
                transition: background 0.15s, color 0.15s !important;
                 border-radius: 0 !important;
            }

            [data-flux-sidebar] [data-flux-navlist-item]:hover {
                background: rgba(255,255,255,0.10) !important;
                color: #ffffff !important;
            }

            /* Active item — gold accent */
            [data-flux-sidebar] [data-flux-navlist-item][data-current],
            [data-flux-sidebar] [data-flux-navlist-item].active,
            [data-flux-sidebar] [data-flux-navlist-item][aria-current] {
                background: rgba(200,150,62,0.18) !important;
                color: #ffffff !important;
                border-left: 3px solid #c8963e !important;
            }

            /* Icons */
            [data-flux-sidebar] [data-flux-navlist-item] svg {
                width: 1.2rem !important;
                height: 1.2rem !important;
                opacity: 0.85;
            }

            [data-flux-sidebar] [data-flux-navlist-item][data-current] svg,
            [data-flux-sidebar] [data-flux-navlist-item][aria-current] svg {
                opacity: 1;
                color: #e8b45a !important;
            }

            /* Profile button at bottom */
            [data-flux-sidebar] [data-flux-profile] {
                background: rgba(255,255,255,0.06) !important;
                border: 1px solid rgba(255,255,255,0.13) !important;
                border-radius: 8px !important;
                color: #ffffff !important;
            }

            [data-flux-sidebar] [data-flux-profile]:hover {
                background: rgba(255,255,255,0.12) !important;
            }

            /* Profile initials circle */
            [data-flux-sidebar] [data-flux-profile] [data-flux-profile-avatar] {
                background: #c8963e !important;
                color: #071628 !important;
                font-weight: 700 !important;
            }

            /* Profile name + email text */
            [data-flux-sidebar] [data-flux-profile] [data-flux-profile-name] {
                color: #ffffff !important;
            }

            [data-flux-sidebar] [data-flux-profile] [data-flux-profile-handle] {
                color: rgba(255,255,255,0.5) !important;
            }

            /* Divider between sections */
            [data-flux-sidebar] hr,
            [data-flux-sidebar] [data-flux-separator] {
                border-color: rgba(255,255,255,0.08) !important;
            }

            /* Sidebar toggle (X button mobile) */
            [data-flux-sidebar] [data-flux-sidebar-toggle] {
                color: rgba(255,255,255,0.7) !important;
            }
            [data-flux-sidebar] .brand-text {
    color: #ffffff !important;
}

[data-flux-sidebar] [data-flux-profile] [data-flux-profile-name],
[data-flux-sidebar] [data-flux-profile] span {
    color: #ffffff !important;
}
        </style>

        <flux:sidebar sticky stashable class="border-r">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

          <a class="flex flex-col items-center justify-center gap-2 px-2 py-6 border-b border-white/10 mb-2" wire:navigate>
    <x-app-logo class="h-16 w-auto" href="#"></x-app-logo>
   <span class="brand-text text-sm font-semibold tracking-wide text-center leading-tight">
    Barangay Service System
</span>


</a>



            @php $roleName = auth()->user()?->role?->role_name; @endphp

            @if(in_array($roleName, ['Admin', 'Secretary']))

                <flux:navlist.group heading="Main Menu" class="grid">
                    <flux:navlist.item icon="chart-pie" : :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        Dashboard
                    </flux:navlist.item>

                     <flux:navlist.item icon="map-pin" :href="route('incident-areas.index')" :current="request()->routeIs('incident-areas.*')" wire:navigate>
            Incident Areas
        </flux:navlist.item>
        
                </flux:navlist.group>

                <flux:navlist.group heading="Services" class="grid">
                    <flux:navlist.item icon="document-text" :href="route('certificates.index')" :current="request()->routeIs('certificates.*')" wire:navigate>
                        Certificates
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('certificates.barangay-clearance')" :current="request()->routeIs('certificates.barangay-clearance')" wire:navigate class="pl-8">
                        Barangay Clearance
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('certificates.barangay-permit')" :current="request()->routeIs('certificates.barangay-permit')" wire:navigate class="pl-8">
                        Barangay Permit
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('certificates.certificate-of-residency')" :current="request()->routeIs('certificates.certificate-of-residency')" wire:navigate class="pl-8">
                        Certificate of Residency
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('certificates.good-moral-certificate')" :current="request()->routeIs('certificates.good-moral-certificate')" wire:navigate class="pl-8">
                        Good Moral Certificate
                    </flux:navlist.item>
                    <flux:navlist.item :href="route('certificates.indigency-certificate')" :current="request()->routeIs('certificates.indigency-certificate')" wire:navigate class="pl-8">
                        Indigency Certificate
                    </flux:navlist.item>
                    <flux:navlist.item icon="shield-check" :href="route('blotter.index')" :current="request()->routeIs('blotter.index')" wire:navigate>
                        Blotter
                    </flux:navlist.item>

             
                </flux:navlist.group>

                @if($roleName === 'Admin')
                    <flux:navlist.group heading="Administration" class="grid">
                        <flux:navlist.item icon="user-group" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>
                            User Management
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endif

            @elseif($roleName === 'Resident')

                <flux:navlist.group heading="Services" class="grid">
                    <flux:navlist.item icon="shield-check" :href="route('blotter.resident_index')" :current="request()->routeIs('blotter.resident_index')" wire:navigate>
                        Blotter Reports
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('certificates.resident_index')" :current="request()->routeIs('certificates.resident_index')" wire:navigate>
                        Certificate Requests
                    </flux:navlist.item>
                </flux:navlist.group>

            @else

                <flux:navlist.group heading="Services" class="grid">
                    <flux:navlist.item icon="shield-check" :href="route('blotter.resident_index')" :current="request()->routeIs('blotter.resident_index')" wire:navigate>
                        Blotter Reports
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('certificates.resident_index')" :current="request()->routeIs('certificates.resident_index')" wire:navigate>
                        Certificate Requests
                    </flux:navlist.item>
                </flux:navlist.group>

            @endif

            <flux:spacer />

            <flux:navlist.group heading="Account" class="grid">
                <flux:navlist.item icon="cog-6-tooth" href="/settings/profile" wire:navigate>
                    Profile
                </flux:navlist.item>
            </flux:navlist.group>

            <!-- Desktop User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />
                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.radio.group>
                        <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Settings</flux:menu.item>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>