<?php

namespace App\Livewire\Attendance;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Nugsoft\HikBridge\Facades\HikBridge;
use Nugsoft\HikBridge\Exceptions\HikBridgeException;

#[Layout('layouts.app', ['title' => 'Attendance Report'])]
class Report extends Component
{
    public string $from = '';
    public string $to = '';
    public ?string $error = null;
    public bool $loading = false;

    // [ 'date' => [ 'person_code' => ['events' => [...], 'first_in' => '...', 'last_out' => '...'] ] ]
    public array $report = [];
    public array $dates = [];
    public array $persons = [];  // unique person_codes present

    public function mount(): void
    {
        $this->from = Carbon::today()->subDays(6)->format('Y-m-d');
        $this->to   = Carbon::today()->format('Y-m-d');
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $this->error  = null;
        $this->report = [];
        $this->dates  = [];
        $this->persons = [];

        $from = Carbon::parse($this->from)->startOfDay();
        $to   = Carbon::parse($this->to)->endOfDay();

        if ($from->gt($to)) {
            $this->error = 'From date must be before To date.';
            return;
        }

        try {
            // Collect all events in the date range via cursor pagination
            $allEvents = [];
            $cursor    = null;

            do {
                $params = [
                    'from'     => $from->toIso8601String(),
                    'to'       => $to->toIso8601String(),
                    'per_page' => 100,
                ];
                if ($cursor) {
                    $params['cursor'] = $cursor;
                }

                $response  = HikBridge::events()->list($params);
                $allEvents = array_merge($allEvents, $response['data'] ?? []);
                $cursor    = $response['meta']['next_cursor'] ?? null;
            } while ($cursor);

            // Build date list
            $period = CarbonPeriod::create($from, $to);
            foreach ($period as $day) {
                $this->dates[] = $day->format('Y-m-d');
            }

            // Group events by date → person_code
            $grouped = [];
            foreach ($allEvents as $event) {
                $code = $event['person_code'] ?? null;
                if (! $code) {
                    continue;
                }

                $occurredAt = $event['occurred_at'] ?? null;
                if (! $occurredAt) {
                    continue;
                }

                $date = Carbon::parse($occurredAt)->format('Y-m-d');

                if (! isset($grouped[$date][$code])) {
                    $grouped[$date][$code] = ['events' => [], 'first_in' => null, 'last_out' => null];
                }

                $grouped[$date][$code]['events'][] = $event;
            }

            // Compute first_in / last_out per person per day
            foreach ($grouped as $date => &$persons) {
                foreach ($persons as $code => &$entry) {
                    $times = array_filter(array_column($entry['events'], 'occurred_at'));
                    if ($times) {
                        sort($times);
                        $entry['first_in']  = Carbon::parse(reset($times))->format('H:i');
                        $entry['last_out']  = Carbon::parse(end($times))->format('H:i');
                        $entry['count']     = count($entry['events']);
                    }
                    unset($entry['events']); // keep it light
                }
            }
            unset($persons, $entry);

            $this->report = $grouped;

            // Unique persons across the period
            $allCodes = [];
            foreach ($grouped as $dayPersons) {
                $allCodes = array_merge($allCodes, array_keys($dayPersons));
            }
            $this->persons = array_values(array_unique($allCodes));
            sort($this->persons);
        } catch (HikBridgeException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.attendance.report');
    }
}
