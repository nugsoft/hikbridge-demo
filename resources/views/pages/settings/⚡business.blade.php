<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Business settings')] class extends Component {
    public string $name = '';

    public string $hik_base_url = '';

    // Left blank on load; only updates the stored key when a new value is entered.
    public string $hik_api_key = '';

    public function mount(): void
    {
        $business = Auth::user()->business;

        $this->name = $business?->name ?? '';
        $this->hik_base_url = $business?->hik_base_url ?? '';
    }

    #[Computed]
    public function maskedKey(): ?string
    {
        return Auth::user()->business?->maskedApiKey();
    }

    public function updateBusiness(): void
    {
        $business = Auth::user()->business;

        abort_if($business === null, 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'hik_base_url' => ['nullable', 'url', 'max:255'],
            'hik_api_key' => ['nullable', 'string', 'max:255', 'starts_with:hbk_'],
        ], [
            'hik_api_key.starts_with' => __('The HikBridge API key must start with "hbk_".'),
        ]);

        $business->name = $validated['name'];
        $business->hik_base_url = $validated['hik_base_url'] ?: null;

        // Only overwrite the key when a new one was actually entered.
        if (! empty($validated['hik_api_key'])) {
            $business->hik_api_key = $validated['hik_api_key'];
        }

        $business->save();

        $this->reset('hik_api_key');

        Flux::toast(variant: 'success', text: __('Business settings updated.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Business settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Business')" :subheading="__('Manage your business and its HikBridge API key')">
        <form wire:submit="updateBusiness" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Business name')" type="text" required autocomplete="organization" />

            <div>
                <flux:input
                    wire:model="hik_api_key"
                    :label="__('HikBridge API key')"
                    type="text"
                    autocomplete="off"
                    placeholder="hbk_..."
                    :description="__('Leave blank to keep the current key. Stored encrypted; every request you make is authenticated with this key.')"
                />

                @if ($this->maskedKey)
                    <flux:text class="mt-2">
                        {{ __('Current key:') }} <span class="font-mono">{{ $this->maskedKey }}</span>
                    </flux:text>
                @endif
            </div>

            <flux:input
                wire:model="hik_base_url"
                :label="__('HikBridge base URL')"
                type="url"
                placeholder="https://hikbridge.example.com/api"
                :description="__('Optional. Overrides the default API base URL for this business.')"
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-business-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
