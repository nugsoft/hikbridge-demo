<?php

namespace App\Livewire;

use App\Models\HikEvent;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Poll;
use Livewire\Component;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;
use Nugsoft\HikBridge\Facades\HikBridge;

#[Layout('layouts.app', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    public ?array $organization = null;

    public int $totalPersons = 0;

    public int $activePersons = 0;

    public int $eventsToday = 0;

    public int $syncPending = 0;

    public int $syncFailed = 0;

    public ?string $lastSynced = null;

    public ?string $error = null;

    public ?string $success = null;

    // Chart data (7-day attendance)
    public array $chartLabels = [];

    public array $chartUnique = [];

    public array $chartTotal = [];

    // Today's roster (unique persons who accessed today)
    public array $todayRoster = [];

    // Live feed (latest events)
    public array $liveFeed = [];

    // Period filter
    public string $period = 'today';

    public function mount(): void
    {
        $this->loadAll();
    }

    public function loadAll(): void
    {
        $this->loadOrg();
        $this->loadPersonStats();
        $this->loadEventStats();
        $this->loadChartData();
        $this->loadTodayRoster();
        $this->loadLiveFeed();
    }

    private function loadOrg(): void
    {
        try {
            $this->organization = HikBridge::business()->get();
        } catch (HikBridgeException) {
        }
    }

    private function loadPersonStats(): void
    {
        try {
            $response = HikBridge::persons()->list(['per_page' => 100]);
            $persons = $response['data'] ?? [];
            $this->totalPersons = count($persons);
            $this->activePersons = count(array_filter($persons, fn ($p) => ($p['status'] ?? '') === 'active'));
            $this->syncPending = count(array_filter($persons, fn ($p) => ($p['sync_status'] ?? '') === 'pending'));
            $this->syncFailed = count(array_filter($persons, fn ($p) => ($p['sync_status'] ?? '') === 'failed'));
        } catch (HikBridgeException) {
        }
    }

    private function loadEventStats(): void
    {
        try {
            $today = Carbon::today();
            $response = HikBridge::events()->list([
                'from' => $today->toIso8601String(),
                'to' => $today->copy()->endOfDay()->toIso8601String(),
                'per_page' => 100,
            ]);
            $this->eventsToday = count($response['data'] ?? []);

            // last synced from most recent event
            $events = $response['data'] ?? [];
            if (count($events) > 0) {
                $latest = $events[0]['occurred_at'] ?? null;
                if ($latest) {
                    $this->lastSynced = Carbon::parse($latest)->diffForHumans();
                }
            }
        } catch (HikBridgeException) {
        }
    }

    private function loadChartData(): void
    {
        try {
            $labels = [];
            $unique = [];
            $totals = [];

            for ($i = 6; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $labels[] = $day->format('D, M j');

                $cursor = null;
                $dayEvents = [];

                do {
                    $params = [
                        'from' => $day->copy()->startOfDay()->toIso8601String(),
                        'to' => $day->copy()->endOfDay()->toIso8601String(),
                        'per_page' => 100,
                    ];
                    if ($cursor) {
                        $params['cursor'] = $cursor;
                    }

                    $response = HikBridge::events()->list($params);
                    $dayEvents = array_merge($dayEvents, $response['data'] ?? []);
                    $cursor = $response['meta']['next_cursor'] ?? null;
                } while ($cursor);

                $uniquePersons = count(array_unique(array_column($dayEvents, 'person_code')));
                $unique[] = $uniquePersons;
                $totals[] = count($dayEvents);
            }

            $this->chartLabels = $labels;
            $this->chartUnique = $unique;
            $this->chartTotal = $totals;
        } catch (HikBridgeException) {
        }
    }

    private function loadTodayRoster(): void
    {
        // Use local webhook events first (instant, no API quota)
        $local = HikEvent::whereDate('occurred_at', Carbon::today())
            ->latest('occurred_at')
            ->get()
            ->unique('person_code')
            ->values();

        if ($local->isNotEmpty()) {
            $this->todayRoster = $local->map(fn ($e) => [
                'event_type' => $e->event_type,
                'person_code' => $e->person_code,
                'device_id' => $e->device_id,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
            ])->toArray();

            return;
        }

        // Fall back to API
        try {
            $today = Carbon::today();
            $response = HikBridge::events()->list([
                'from' => $today->toIso8601String(),
                'to' => $today->copy()->endOfDay()->toIso8601String(),
                'per_page' => 100,
            ]);
            $events = $response['data'] ?? [];

            $seen = [];
            foreach ($events as $event) {
                $code = $event['person_code'] ?? null;
                if ($code && ! isset($seen[$code])) {
                    $seen[$code] = $event;
                }
            }
            $this->todayRoster = array_values($seen);
        } catch (HikBridgeException) {
        }
    }

    private function loadLiveFeed(): void
    {
        // Prefer locally stored webhook events (real-time, no API call needed)
        $local = HikEvent::latest('occurred_at')->limit(10)->get();

        if ($local->isNotEmpty()) {
            $this->liveFeed = $local->map(fn ($e) => [
                'event_type' => $e->event_type,
                'person_code' => $e->person_code,
                'device_id' => $e->device_id,
                'device_name' => $e->device_name,
                'occurred_at' => $e->occurred_at?->toIso8601String(),
            ])->toArray();

            return;
        }

        // Fall back to API if no webhook events stored yet
        try {
            $response = HikBridge::events()->list(['per_page' => 10]);
            $this->liveFeed = $response['data'] ?? [];
        } catch (HikBridgeException) {
        }
    }

    #[Poll(5000)] // re-fetch live feed every 5 seconds
    public function pollLiveFeed(): void
    {
        $this->loadLiveFeed();
        $this->loadEventStats();
    }

    public function triggerSync(): void
    {
        try {
            HikBridge::events()->triggerSync();
            $this->success = 'Sync triggered.';
            $this->loadEventStats();
            $this->loadLiveFeed();
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
