<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Observers\AppointmentObserver;
use App\Observers\BillObserver;
use App\Observers\EncounterObserver;
use App\Observers\PatientObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Appointment::observe(AppointmentObserver::class);
        Patient::observe(PatientObserver::class);
        User::observe(UserObserver::class);
        Encounter::observe(EncounterObserver::class);
        Bill::observe(BillObserver::class);
    }
}
