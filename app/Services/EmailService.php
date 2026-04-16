<?php

namespace App\Services;

use App\Mail\AppointmentReminderMail;
use App\Mail\PrescriptionMail;
use App\Mail\ReportMail;
use App\Models\Appointment;
use App\Models\Prescription;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendPrescription(int $prescriptionId, ?string $patientEmail = null): bool
    {
        $prescription = Prescription::with(['patient', 'doctor', 'items'])->find($prescriptionId);

        if (! $prescription) {
            return false;
        }

        $email = $patientEmail ?? $prescription->patient?->email;

        if (! $email) {
            return false;
        }

        try {
            $pdfContent = (new PdfService)->generatePrescription($prescription);
            $tempPath = storage_path("app/temp/prescription_{$prescription->id}.pdf");
            if (! is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            file_put_contents($tempPath, $pdfContent);

            Mail::to($email)->send(new PrescriptionMail(
                prescription: $prescription->toArray(),
                patient: $prescription->patient?->toArray() ?? [],
                doctor: $prescription->doctor?->toArray() ?? [],
                pdfPath: $tempPath
            ));

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send prescription email: '.$e->getMessage());

            return false;
        }
    }

    public function sendAppointmentReminder(int $appointmentId): bool
    {
        $appointment = Appointment::with(['patient', 'doctor', 'service', 'clinic'])->find($appointmentId);

        if (! $appointment) {
            return false;
        }

        $email = $appointment->patient?->email;

        if (! $email) {
            return false;
        }

        try {
            Mail::to($email)->send(new AppointmentReminderMail(
                appointment: $appointment->toArray(),
                patient: $appointment->patient?->toArray() ?? [],
                doctor: $appointment->doctor?->toArray() ?? [],
                clinicName: $appointment->clinic?->name ?? 'Our Clinic'
            ));

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send appointment reminder: '.$e->getMessage());

            return false;
        }
    }

    public function sendReport(string $reportType, array $data, string $email, string $clinicName, string $dateRange = ''): bool
    {
        try {
            Mail::to($email)->send(new ReportMail(
                reportType: $reportType,
                data: $data,
                clinicName: $clinicName,
                dateRange: $dateRange
            ));

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send report email: '.$e->getMessage());

            return false;
        }
    }

    public function sendBulkAppointmentReminders(): array
    {
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Appointment::where('appointment_date', $tomorrow)
            ->where('status', 'confirmed')
            ->with(['patient', 'doctor'])
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($appointments as $appointment) {
            if ($this->sendAppointmentReminder($appointment->id)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
