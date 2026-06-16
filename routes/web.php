<?php

use App\Livewire\Dashboard;
use App\Livewire\Employees\Index as EmployeesIndex;
use App\Livewire\Employees\Show as EmployeeShow;
use App\Livewire\Devices\Index as DevicesIndex;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Webhooks\Index as WebhooksIndex;
use App\Livewire\Attendance\Report as AttendanceReport;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Public — no auth, no CSRF (HikBridge signs with HMAC instead)
Route::post('/webhooks/hikbridge', [WebhookController::class, 'receive'])
    ->name('webhooks.receive')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/employees', EmployeesIndex::class)->name('employees.index');
    Route::get('/employees/{personId}', EmployeeShow::class)->name('employees.show');
    Route::get('/devices', DevicesIndex::class)->name('devices.index');
    Route::get('/events', EventsIndex::class)->name('events.index');
    Route::get('/webhooks', WebhooksIndex::class)->name('webhooks.index');
    Route::get('/attendance', AttendanceReport::class)->name('attendance.report');
});

require __DIR__.'/settings.php';
