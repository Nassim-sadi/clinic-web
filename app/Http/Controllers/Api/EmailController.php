<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Clinic;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function sendReport(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|string',
            'email' => 'required|email',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $reportType = $request->report_type;
        $email = $request->email;
        $dateFrom = $request->date_from ?? now()->startOfMonth()->toDateString();
        $dateTo = $request->date_to ?? now()->endOfMonth()->toDateString();

        $clinicId = $request->user()->clinic_id ?? session('active_clinic');
        $clinicName = Clinic::find($clinicId)?->name ?? 'Clinic';

        $dateRange = date('M d, Y', strtotime($dateFrom)).' - '.date('M d, Y', strtotime($dateTo));

        $reportData = $this->generateReportData($reportType, $clinicId, $dateFrom, $dateTo);

        $sent = app(EmailService::class)->sendReport($reportType, $reportData, $email, $clinicName, $dateRange);

        if ($sent) {
            return response()->json(['message' => 'Report sent successfully to '.$email]);
        }

        return response()->json(['message' => 'Failed to send report'], 500);
    }

    private function generateReportData(string $type, ?int $clinicId, string $dateFrom, string $dateTo): array
    {
        return match ($type) {
            'appointments' => $this->getAppointmentsReportData($clinicId, $dateFrom, $dateTo),
            'patients' => $this->getPatientsReportData($clinicId),
            'billing' => $this->getBillingReportData($clinicId, $dateFrom, $dateTo),
            'summary' => $this->getSummaryReportData($clinicId),
            default => ['items' => []],
        };
    }

    private function getAppointmentsReportData(?int $clinicId, string $dateFrom, string $dateTo): array
    {
        $query = Appointment::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('date', [$dateFrom, $dateTo]);

        return [
            'summary' => [
                'total' => $query->count(),
                'completed' => (clone $query)->where('status', 'completed')->count(),
                'pending' => (clone $query)->where('status', 'pending')->count(),
                'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            ],
            'items' => $query->orderBy('date', 'desc')->get()->map(fn ($a) => [
                'date' => $a->date,
                'time' => $a->start_time,
                'patient' => $a->patient?->name ?? 'N/A',
                'doctor' => $a->doctor?->name ?? 'N/A',
                'status' => $a->status,
            ])->toArray(),
        ];
    }

    private function getPatientsReportData(?int $clinicId): array
    {
        $query = User::query()
            ->with(['patient'])
            ->where('role', 'patient')
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId));

        return [
            'summary' => [
                'total' => $query->count(),
                'new_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            ],
            'items' => $query->orderBy('created_at', 'desc')->get()->map(fn ($p) => [
                'name' => $p->name,
                'email' => $p->email,
                'phone' => $p->phone ?? 'N/A',
                'gender' => $p->patient?->gender ?? 'N/A',
                'registered' => $p->created_at->format('Y-m-d'),
            ])->toArray(),
        ];
    }

    private function getBillingReportData(?int $clinicId, string $dateFrom, string $dateTo): array
    {
        $query = Bill::query()
            ->with(['patient:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        return [
            'summary' => [
                'total_bills' => $query->count(),
                'total_amount' => (clone $query)->sum('total_amount'),
                'total_paid' => (clone $query)->where('payment_status', 'paid')->sum('amount_paid'),
                'total_unpaid' => (clone $query)->where('payment_status', '!=', 'paid')->sum('total_amount'),
            ],
            'items' => $query->orderBy('created_at', 'desc')->get()->map(fn ($b) => [
                'invoice' => $b->invoice_number,
                'patient' => $b->patient?->name ?? 'N/A',
                'total' => $b->total_amount,
                'paid' => $b->amount_paid ?? 0,
                'status' => $b->payment_status,
                'date' => $b->created_at->format('Y-m-d'),
            ])->toArray(),
        ];
    }

    private function getSummaryReportData(?int $clinicId): array
    {
        return [
            'summary' => [
                'total_patients' => User::where('role', 'patient')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
                'total_doctors' => User::where('role', 'doctor')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
                'month_appointments' => Appointment::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->whereMonth('date', now()->month)->count(),
                'month_revenue' => Bill::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->whereMonth('created_at', now()->month)->where('payment_status', 'paid')->sum('amount_paid'),
            ],
            'items' => [],
        ];
    }

    public function sendReminder(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);

        $sent = app(EmailService::class)->sendAppointmentReminder($request->appointment_id);

        if ($sent) {
            return response()->json(['message' => 'Reminder sent successfully']);
        }

        return response()->json(['message' => 'Failed to send reminder. Make sure patient has an email.'], 422);
    }

    public function sendBulkReminders(Request $request): JsonResponse
    {
        $result = app(EmailService::class)->sendBulkAppointmentReminders();

        return response()->json([
            'message' => 'Reminders processed',
            'sent' => $result['sent'],
            'failed' => $result['failed'],
        ]);
    }
}
