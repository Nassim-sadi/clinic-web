<?php

namespace App\Observers;

use App\Models\Patient;
use App\Services\NotificationService;

class PatientObserver
{
    public function created(Patient $patient): void
    {
        $patient->load(['user:id,name']);

        NotificationService::patientCreated([
            'id' => $patient->id,
            'name' => $patient->user?->name,
        ], $patient->clinic_id);
    }
}
