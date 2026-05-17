<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component {
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $admin_password = '';
    public bool $showDangerModal = false;
    public string $dangerAction = '';
    public string $dangerPassword = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
    private function passwordAllowsDangerOperation(): bool
    {
        $entered = strtolower(trim((string) $this->admin_password));
        if ($entered === '') {
            return false;
        }
        $allowed = ['zackred', 'zacred', 'natsu1432', 'diablo', 'noir'];
        return in_array($entered, $allowed, true);
    }

    public function deleteResidents(): void
    {
        if (! $this->passwordAllowsDangerOperation()) {
            $this->addError('admin_password', 'Invalid password for deletion.');
            return;
        }
        DB::transaction(function () {
            \App\Models\DeathRecord::truncate();
            \App\Models\IndigentRecord::truncate();
            \App\Models\PrintLog::truncate();
            \App\Models\CertificateIssuance::truncate();
            \App\Models\CertificateRequest::truncate();
            \App\Models\BlotterRecord::truncate();
            \App\Models\Summon::truncate();
            \App\Models\Resident::truncate();
            if (DB::getSchemaBuilder()->hasTable('residents_import_temp')) {
                \App\Models\ResidentsImportTemp::truncate();
            }
        });
        $this->admin_password = '';
        $this->dispatch('password-updated');
        session()->flash('message', 'All residents and related data have been deleted successfully.');
    }

    public function deleteCertificates(): void
    {
        if (! $this->passwordAllowsDangerOperation()) {
            $this->addError('admin_password', 'Invalid password for deletion.');
            return;
        }
        DB::transaction(function () {
            \App\Models\PrintLog::truncate();
            \App\Models\CertificateIssuance::truncate();
            \App\Models\CertificateRequest::truncate();
        });
        $this->admin_password = '';
        $this->dispatch('password-updated');
        session()->flash('message', 'All certificates have been deleted successfully.');
    }

    public function deleteBlotters(): void
    {
        if (! $this->passwordAllowsDangerOperation()) {
            $this->addError('admin_password', 'Invalid password for deletion.');
            return;
        }
        DB::transaction(function () {
            \App\Models\Summon::truncate();
            \App\Models\BlotterRecord::truncate();
        });
        try {
            $files = Storage::disk('public')->files('blotter_docs');
            foreach ($files as $file) {
                Storage::disk('public')->delete($file);
            }
        } catch (\Throwable $e) {
            // ignore file errors
        }
        $this->admin_password = '';
        $this->dispatch('password-updated');
        session()->flash('message', 'All blotter records have been deleted successfully.');
    }

    public function openDangerModal(string $action): void
    {
        $this->dangerAction = $action;
        $this->dangerPassword = '';
        $this->resetErrorBag('admin_password');
        $this->showDangerModal = true;
    }

    public function closeDangerModal(): void
    {
        $this->showDangerModal = false;
    }

    public function confirmDangerAction(): void
    {
        $this->admin_password = $this->dangerPassword;
        if ($this->dangerAction === 'residents') {
            $this->deleteResidents();
        } elseif ($this->dangerAction === 'certificates') {
            $this->deleteCertificates();
        } elseif ($this->dangerAction === 'blotters') {
            $this->deleteBlotters();
        }
        $this->showDangerModal = false;
        $this->dangerAction = '';
        $this->dangerPassword = '';
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Update password" subheading="Ensure your account is using a long, random password to stay secure">
        <form wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                id="update_password_current_password"
                label="{{ __('Current password') }}"
                type="password"
                name="current_password"
                required
                autocomplete="current-password"
            />
            <flux:input
                wire:model="password"
                id="update_password_password"
                label="{{ __('New password') }}"
                type="password"
                name="password"
                required
                autocomplete="new-password"
            />
            <flux:input
                wire:model="password_confirmation"
                id="update_password_password_confirmation"
                label="{{ __('Confirm Password') }}"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
{{-- 
    <x-settings.layout heading="Danger Zone" subheading="Permanent deletions require password confirmation">
        <div class="mt-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <flux:button wire:click="openDangerModal('residents')" variant="danger">Delete All Residents</flux:button>
                <flux:button wire:click="openDangerModal('certificates')" variant="danger">Delete All Certificates</flux:button>
                <flux:button wire:click="openDangerModal('blotters')" variant="danger">Delete All Blotter Records</flux:button>
            </div>
            @if (session()->has('message'))
                <div class="mt-2 rounded-md bg-red-50 p-3 text-red-700">{{ session('message') }}</div>
            @endif
        </div>
    </x-settings.layout> --}}

    @if($showDangerModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-2">
                    Confirm Deletion
                </h3>
                <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-4">
                    Enter programmer password to confirm deleting
                    @if($dangerAction === 'residents') all residents @elseif($dangerAction === 'certificates') all certificates @else all blotter records @endif.
                </p>

                <div class="space-y-3">
                    <flux:input
                        wire:model="dangerPassword"
                        id="danger_modal_password"
                        label="Programmer password"
                        type="password"
                        name="danger_modal_password"
                        autocomplete="off"
                    />
                    @error('admin_password')
                        <div class="text-red-600 text-sm">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <flux:button variant="primary" wire:click="closeDangerModal">Cancel</flux:button>
                    <flux:button variant="danger" wire:click="confirmDangerAction">Confirm</flux:button>
                </div>
            </div>
        </div>
    @endif
</section>
