<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\NotificationService;

class AppointmentObserver
{
    public function created(Appointment $appointment): void
    {
        $appointment->load(['patient:id,name', 'doctor:id,name']);

        NotificationService::appointmentCreated([
            'id' => $appointment->id,
            'patient_name' => $appointment->patient?->name,
            'doctor_name' => $appointment->doctor?->name,
            'doctor_id' => $appointment->doctor_id,
            'date' => $appointment->date?->format('Y-m-d'),
        ], $appointment->clinic_id);
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('status') && $appointment->status === Appointment::STATUS_CANCELLED) {
            NotificationService::appointmentCancelled([
                'id' => $appointment->id,
                'patient_name' => $appointment->patient?->name,
                'doctor_id' => $appointment->doctor_id,
            ], $appointment->clinic_id);
        } elseif ($appointment->wasChanged('status')) {
            NotificationService::appointmentUpdated([
                'id' => $appointment->id,
                'patient_name' => $appointment->patient?->name,
                'doctor_id' => $appointment->doctor_id,
                'status' => $appointment->status,
            ], $appointment->clinic_id);
        }
    }
}
