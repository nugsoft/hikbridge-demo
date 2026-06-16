<?php

namespace App\Livewire\Events;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;

#[Layout('layouts.app', ['title' => 'Access Events'])]
class Index extends Component
{
    public array $events = [];
    public ?string $nextCursor = null;
    public ?string $prevCursor = null;
    public ?string $error = null;
    public ?string $success = null;

    // Filters
    public string $filterPersonCode = '';
    public string $filterEventType = '';
    public string $filterFrom = '';
    public string $filterTo = '';
    public int $perPage = 25;

    public ?string $cursor = null;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function loadEvents(): void
    {
        try {
            $params = ['per_page' => $this->perPage];

            if ($this->cursor) {
                $params['cursor'] = $this->cursor;
            }
            if ($this->filterPersonCode) {
                $params['person_code'] = $this->filterPersonCode;
            }
            if ($this->filterEventType) {
                $params['event_type'] = $this->filterEventType;
            }
            if ($this->filterFrom) {
                $params['from'] = $this->filterFrom;
            }
            if ($this->filterTo) {
                $params['to'] = $this->filterTo;
            }

            $response = HikBridge::events()->list($params);
            $this->events = $response['data'] ?? [];
            $this->nextCursor = $response['meta']['next_cursor'] ?? null;
            $this->error = null;
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function applyFilters(): void
    {
        $this->cursor = null;
        $this->loadEvents();
    }

    public function resetFilters(): void
    {
        $this->cursor = null;
        $this->filterPersonCode = '';
        $this->filterEventType = '';
        $this->filterFrom = '';
        $this->filterTo = '';
        $this->loadEvents();
    }

    public function nextPage(): void
    {
        $this->cursor = $this->nextCursor;
        $this->loadEvents();
    }

    public function triggerSync(): void
    {
        try {
            HikBridge::events()->triggerSync();
            $this->success = 'Sync triggered. New events will appear shortly.';
            $this->cursor = null;
            $this->loadEvents();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.events.index');
    }
}
