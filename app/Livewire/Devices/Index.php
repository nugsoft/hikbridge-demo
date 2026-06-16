<?php

namespace App\Livewire\Devices;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;

#[Layout('layouts.app', ['title' => 'Devices'])]
class Index extends Component
{
    public array $devices = [];
    public ?string $error = null;
    public ?string $success = null;

    public function mount(): void
    {
        $this->loadDevices();
    }

    public function loadDevices(): void
    {
        try {
            $response = HikBridge::devices()->list(['per_page' => 100]);
            $this->devices = $response['data'] ?? [];
            $this->error = null;
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.devices.index');
    }
}
