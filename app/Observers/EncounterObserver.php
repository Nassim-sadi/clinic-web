<?php

namespace App\Observers;

use App\Models\Encounter;
use App\Services\NotificationService;

class EncounterObserver
{
    public function created(Encounter $encounter): void
    {
        $encounter->load(['patient:id,name']);

        NotificationService::encounterCreated([
            'id' => $encounter->id,
            'patient_name' => $encounter->patient?->name,
        ], $encounter->clinic_id);
    }
}
