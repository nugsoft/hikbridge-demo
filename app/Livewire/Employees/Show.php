<?php

namespace App\Livewire\Employees;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;

#[Layout('layouts.app', ['title' => 'Employee'])]
class Show extends Component
{
    use WithFileUploads;

    public int $personId;
    public ?array $person = null;
    public array $devices = [];
    public ?int $selectedDeviceId = null;

    public ?string $error = null;
    public ?string $success = null;

    // Biometric summary (refreshed per device)
    public ?array $summary = null;

    // Face
    public $faceImage = null;

    // Fingerprint
    public int $fingerIndex = 0;

    // Access card
    public string $cardNo = '';
    public int $cardType = 1;

    // Finger labels
    protected array $fingerLabels = [
        0 => 'Right Thumb',
        1 => 'Right Index',
        2 => 'Right Middle',
        3 => 'Right Ring',
        4 => 'Right Little',
        5 => 'Left Thumb',
        6 => 'Left Index',
        7 => 'Left Middle',
        8 => 'Left Ring',
        9 => 'Left Little',
    ];

    public function mount(int $personId): void
    {
        $this->personId = $personId;
        $this->loadPerson();
        $this->loadDevices();
        $this->loadSummary();
    }

    public function updatedSelectedDeviceId(): void
    {
        $this->loadSummary();
    }

    private function loadPerson(): void
    {
        try {
            $this->person = HikBridge::persons()->get($this->personId);
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadDevices(): void
    {
        try {
            $response = HikBridge::devices()->list(['per_page' => 100]);
            $this->devices = $response['data'] ?? [];
            if ($this->selectedDeviceId === null && count($this->devices) > 0) {
                $this->selectedDeviceId = $this->devices[0]['id'];
            }
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadSummary(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }
        try {
            $response      = HikBridge::biometrics($this->personId)->summary($this->selectedDeviceId);
            $this->summary = $response['data'] ?? $response;
            $this->error   = null;
        } catch (HikBridgeException $e) {
            $this->summary = null;
            $this->error   = $e->getMessage();
        }
    }

    // ── Face ──────────────────────────────────────────────────────────────

    public function uploadFace(): void
    {
        $this->validate(['faceImage' => 'required|image|max:5120']);

        if (! $this->selectedDeviceId) {
            $this->error = 'Select a device first.';
            return;
        }

        try {
            $base64 = base64_encode(file_get_contents($this->faceImage->getRealPath()));
            HikBridge::biometrics($this->personId)->uploadFace($this->selectedDeviceId, $base64);
            $this->success   = 'Face uploaded successfully.';
            $this->faceImage = null;
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function captureFace(): void
    {
        if (! $this->selectedDeviceId) {
            $this->error = 'Select a device first.';
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->captureFace($this->selectedDeviceId);
            $this->success = 'Face capture initiated — stand in front of the camera.';
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function deleteFace(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->deleteFace($this->selectedDeviceId);
            $this->success = 'Face deleted.';
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ── Fingerprint ───────────────────────────────────────────────────────

    public function captureFingerprint(): void
    {
        if (! $this->selectedDeviceId) {
            $this->error = 'Select a device first.';
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->captureFingerprint($this->selectedDeviceId, $this->fingerIndex);
            $this->success = "Fingerprint capture initiated for {$this->fingerLabels[$this->fingerIndex]}.";
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function deleteFingerprint(int $fingerIndex): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->deleteFingerprint($this->selectedDeviceId, $fingerIndex);
            $this->success = "Fingerprint ({$this->fingerLabels[$fingerIndex]}) deleted.";
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ── Access Card ───────────────────────────────────────────────────────

    public function addCard(): void
    {
        $this->validate(['cardNo' => 'required|string|max:50']);

        if (! $this->selectedDeviceId) {
            $this->error = 'Select a device first.';
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->addAccessCard($this->selectedDeviceId, $this->cardNo, $this->cardType);
            $this->success = "Card {$this->cardNo} added.";
            $this->cardNo  = '';
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function deleteCard(string $cardNo): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        try {
            HikBridge::biometrics($this->personId)->deleteAccessCard($this->selectedDeviceId, $cardNo);
            $this->success = "Card {$cardNo} removed.";
            $this->loadSummary();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function getFingerLabelsProperty(): array
    {
        return $this->fingerLabels;
    }

    public function render()
    {
        return view('livewire.employees.show', [
            'fingerLabels' => $this->fingerLabels,
        ]);
    }
}
