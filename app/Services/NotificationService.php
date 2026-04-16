<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function patientCreated(array $patient, int $clinicId): void
    {
        $patientName = $patient['name'] ?? 'New Patient';
        Notification::notifyAdmins(
            Notification::TYPE_PATIENT_CREATED,
            'New Patient Registered',
            "A new patient ({$patientName}) has been registered.",
            ['patient_id' => $patient['id'] ?? null, 'clinic_id' => $clinicId]
        );
    }

    public static function appointmentCreated(array $appointment, int $clinicId): void
    {
        $patientName = $appointment['patient_name'] ?? 'Unknown';
        $doctorName = $appointment['doctor_name'] ?? 'Unknown';
        $date = $appointment['date'] ?? '';

        Notification::notifyAdmins(
            Notification::TYPE_APPOINTMENT_CREATED,
            'New Appointment',
            "New appointment for {$patientName} with Dr. {$doctorName} on {$date}",
            ['appointment_id' => $appointment['id'] ?? null, 'clinic_id' => $clinicId]
        );

        if (isset($appointment['doctor_id'])) {
            Notification::notifyDoctor(
                $appointment['doctor_id'],
                Notification::TYPE_APPOINTMENT_CREATED,
                'New Appointment',
                "You have a new appointment with {$patientName} on {$date}",
                ['appointment_id' => $appointment['id'] ?? null]
            );
        }
    }

    public static function appointmentUpdated(array $appointment, int $clinicId): void
    {
        $patientName = $appointment['patient_name'] ?? 'Unknown';
        $status = $appointment['status'] ?? '';

        Notification::notifyAdmins(
            Notification::TYPE_APPOINTMENT_UPDATED,
            'Appointment Updated',
            "Appointment for {$patientName} has been updated to '{$status}'",
            ['appointment_id' => $appointment['id'] ?? null, 'clinic_id' => $clinicId]
        );

        if (isset($appointment['doctor_id'])) {
            Notification::notifyDoctor(
                $appointment['doctor_id'],
                Notification::TYPE_APPOINTMENT_UPDATED,
                'Appointment Updated',
                "Your appointment with {$patientName} has been updated",
                ['appointment_id' => $appointment['id'] ?? null]
            );
        }
    }

    public static function appointmentCancelled(array $appointment, int $clinicId): void
    {
        $patientName = $appointment['patient_name'] ?? 'Unknown';

        Notification::notifyAdmins(
            Notification::TYPE_APPOINTMENT_CANCELLED,
            'Appointment Cancelled',
            "Appointment for {$patientName} has been cancelled.",
            ['appointment_id' => $appointment['id'] ?? null, 'clinic_id' => $clinicId]
        );

        if (isset($appointment['doctor_id'])) {
            Notification::notifyDoctor(
                $appointment['doctor_id'],
                Notification::TYPE_APPOINTMENT_CANCELLED,
                'Appointment Cancelled',
                "Appointment with {$patientName} has been cancelled",
                ['appointment_id' => $appointment['id'] ?? null]
            );
        }
    }

    public static function doctorStatusChanged(array $doctor, string $status): void
    {
        $doctorName = $doctor['name'] ?? 'Unknown Doctor';
        $statusLabel = ucfirst($status);

        Notification::notifyAdmins(
            Notification::TYPE_DOCTOR_STATUS_CHANGED,
            'Doctor Status Changed',
            "Dr. {$doctorName} status changed to '{$statusLabel}'",
            ['doctor_id' => $doctor['id'] ?? null]
        );
    }

    public static function encounterCreated(array $encounter, int $clinicId): void
    {
        $patientName = $encounter['patient_name'] ?? 'Unknown';

        Notification::notifyAdmins(
            Notification::TYPE_ENCOUNTER_CREATED,
            'New Encounter',
            "New encounter created for {$patientName}",
            ['encounter_id' => $encounter['id'] ?? null, 'clinic_id' => $clinicId]
        );
    }

    public static function billCreated(array $bill, int $clinicId): void
    {
        $patientName = $bill['patient_name'] ?? 'Unknown';
        $amount = $bill['total_amount'] ?? 0;

        Notification::notifyAdmins(
            Notification::TYPE_BILL_CREATED,
            'New Bill Created',
            "Invoice created for {$patientName} - \${$amount}",
            ['bill_id' => $bill['id'] ?? null, 'clinic_id' => $clinicId]
        );
    }

    public static function billPaid(array $bill, int $clinicId): void
    {
        $patientName = $bill['patient_name'] ?? 'Unknown';
        $amount = $bill['amount_paid'] ?? 0;

        Notification::notifyAdmins(
            Notification::TYPE_BILL_PAID,
            'Payment Received',
            "Payment of \${$amount} received from {$patientName}",
            ['bill_id' => $bill['id'] ?? null, 'clinic_id' => $clinicId]
        );
    }
}
