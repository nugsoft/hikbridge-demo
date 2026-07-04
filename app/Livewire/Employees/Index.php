<?php

namespace App\Livewire\Employees;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;
use Nugsoft\HikBridge\Exceptions\ValidationException;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\PendingOperation;

#[Layout('layouts.app', ['title' => 'Employees'])]
class Index extends Component
{
    public array $persons = [];

    public array $devices = [];

    public ?string $orgName = null;

    public ?string $error = null;

    public ?string $success = null;

    // Create modal
    public bool $showCreateModal = false;

    public string $person_code = '';

    public string $first_name = '';

    public string $last_name = '';

    public string $status = 'active';

    // Edit modal
    public bool $showEditModal = false;

    public ?int $editingPersonId = null;

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editStatus = 'active';

    public string $editPersonCode = '';

    // Sync to device modal
    public bool $showSyncModal = false;

    public ?int $syncingPersonId = null;

    public ?int $syncDeviceId = null;

    public function mount(): void
    {
        $this->loadPersons();
        $this->loadDevices();
        $this->loadOrg();
    }

    public function loadPersons(): void
    {
        try {
            $response = HikBridge::persons()->list(['per_page' => 100]);
            $this->persons = $response['data'] ?? [];
            $this->error = null;
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function loadDevices(): void
    {
        try {
            $response = HikBridge::devices()->list(['per_page' => 100]);
            $this->devices = $response['data'] ?? [];
            if ($this->syncDeviceId === null && count($this->devices) > 0) {
                $this->syncDeviceId = $this->devices[0]['id'];
            }
        } catch (HikBridgeException) {
            // non-fatal
        }
    }

    public function loadOrg(): void
    {
        try {
            $response = HikBridge::business()->get();
            $this->orgName = $response['data']['name'] ?? null;
        } catch (HikBridgeException) {
            // non-fatal
        }
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function createPerson(): void
    {
        $this->validate([
            'person_code' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $result = HikBridge::persons()->create([
                'person_code' => $this->person_code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'status' => $this->status,
            ]);

            if ($result instanceof PendingOperation) {
                $result->waitUntilDone(timeout: 30);
                $this->success = "{$this->first_name} {$this->last_name} created and synced to all devices.";
            } else {
                $this->success = "{$this->first_name} {$this->last_name} created.";
            }

            $this->reset(['person_code', 'first_name', 'last_name', 'showCreateModal']);
            $this->status = 'active';
            $this->loadPersons();
        } catch (ValidationException $e) {
            $this->addError('person_code', collect($e->errors())->flatten()->first() ?? 'Validation failed.');
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function openEditModal(int $personId): void
    {
        $person = collect($this->persons)->firstWhere('id', $personId);

        if (! $person) {
            return;
        }

        $this->editingPersonId = $personId;
        $this->editPersonCode = $person['person_code'] ?? '';
        $this->editFirstName = $person['first_name'] ?? '';
        $this->editLastName = $person['last_name'] ?? '';
        $this->editStatus = $person['status'] ?? 'active';
        $this->showEditModal = true;
    }

    public function updatePerson(): void
    {
        $this->validate([
            'editFirstName' => 'required|string|max:100',
            'editLastName' => 'required|string|max:100',
            'editStatus' => 'required|in:active,inactive',
        ]);

        try {
            HikBridge::persons()->update($this->editingPersonId, [
                'first_name' => $this->editFirstName,
                'last_name' => $this->editLastName,
                'status' => $this->editStatus,
            ]);

            $this->success = "{$this->editFirstName} {$this->editLastName} updated.";
            $this->reset(['showEditModal', 'editingPersonId', 'editFirstName', 'editLastName', 'editPersonCode']);
            $this->editStatus = 'active';
            $this->loadPersons();
        } catch (ValidationException $e) {
            $this->addError('editFirstName', collect($e->errors())->flatten()->first() ?? 'Validation failed.');
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ─── Sync to Device ───────────────────────────────────────────────────

    public function openSyncModal(int $personId): void
    {
        $this->syncingPersonId = $personId;
        $this->showSyncModal = true;
        if (empty($this->devices)) {
            $this->loadDevices();
        }
    }

    public function syncToDevice(): void
    {
        if (! $this->syncDeviceId || ! $this->syncingPersonId) {
            return;
        }

        $person = collect($this->persons)->firstWhere('id', $this->syncingPersonId);

        try {
            HikBridge::persons()->create([
                'person_code' => $person['person_code'],
                'first_name' => $person['first_name'] ?? '',
                'last_name' => $person['last_name'] ?? '',
                'status' => $person['status'] ?? 'active',
                'device_id' => $this->syncDeviceId,
            ]);

            $deviceName = collect($this->devices)->firstWhere('id', $this->syncDeviceId)['name'] ?? "Device #{$this->syncDeviceId}";
            $this->success = "Synced to {$deviceName}.";
            $this->reset(['showSyncModal', 'syncingPersonId']);
            $this->loadPersons();
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first() ?? 'Validation failed.';
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ─── Disable / Enable Access ──────────────────────────────────────────

    public function disableAccess(int $personId): void
    {
        try {
            HikBridge::persons()->update($personId, ['status' => 'inactive']);
            $this->success = 'Access disabled.';
            $this->loadPersons();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function enableAccess(int $personId): void
    {
        try {
            HikBridge::persons()->update($personId, ['status' => 'active']);
            $this->success = 'Access enabled.';
            $this->loadPersons();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function deletePerson(int $personId): void
    {
        try {
            $op = HikBridge::persons()->delete($personId);
            $op->waitUntilDone(timeout: 30);
            $this->success = 'Employee deleted from all devices.';
            $this->loadPersons();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.employees.index');
    }
}
