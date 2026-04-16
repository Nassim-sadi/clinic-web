<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $report = $request->get('type', 'summary');

        return match ($report) {
            'appointments' => $this->appointmentsReport($request),
            'patients' => $this->patientsReport($request),
            'billing' => $this->billingReport($request),
            'doctors' => $this->doctorsReport($request),
            'summary' => $this->summaryReport($request),
            default => response()->json(['message' => 'Invalid report type'], 400),
        };
    }

    public function export(Request $request)
    {
        $report = $request->get('type', 'appointments');
        $format = $request->get('format', 'csv');

        if ($format === 'xlsx') {
            return $this->exportExcel($report, $request);
        }

        return match ($report) {
            'appointments' => $this->exportAppointments($request, $format),
            'patients' => $this->exportPatients($request, $format),
            'billing' => $this->exportBilling($request, $format),
            default => response()->json(['message' => 'Invalid report type'], 400),
        };
    }

    private function exportExcel(string $report, Request $request)
    {
        $exportService = app(ExportService::class);
        $fromDate = $request->get('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->get('to_date', now()->endOfMonth()->toDateString());

        $data = match ($report) {
            'appointments' => $this->getAppointmentsData($request),
            'patients' => $this->getPatientsData($request),
            'billing' => $this->getBillingData($request),
            default => [],
        };

        $path = match ($report) {
            'appointments' => $exportService->exportAppointments($data, $fromDate, $toDate),
            'patients' => $exportService->exportPatients($data),
            'billing' => $exportService->exportBilling($data, $fromDate, $toDate),
            default => null,
        };

        if (! $path) {
            return response()->json(['message' => 'Export failed'], 500);
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    private function appointmentsReport(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        $query = Appointment::query()
            ->with(['patient:id,name', 'doctor:id,name', 'service:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('date', [$fromDate, $toDate]);

        $stats = [
            'total' => $query->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'confirmed' => (clone $query)->where('status', 'confirmed')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'cancelled' => (clone $query)->where('status', 'cancelled')->count(),
            'no_show' => (clone $query)->where('status', 'no_show')->count(),
        ];

        $appointments = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'stats' => $stats,
            'appointments' => $appointments,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ]);
    }

    private function patientsReport(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $query = User::query()
            ->with(['patient', 'clinic:id,name'])
            ->where('role', 'patient')
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId));

        $stats = [
            'total' => $query->count(),
            'new_this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'with_appointments' => (clone $query)->has('patient.appointments')->count(),
        ];

        $patients = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'stats' => $stats,
            'patients' => $patients,
        ]);
    }

    private function billingReport(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        $query = Bill::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('created_at', [$fromDate, $toDate]);

        $stats = [
            'total_bills' => $query->count(),
            'total_amount' => (clone $query)->sum('total_amount'),
            'total_paid' => (clone $query)->where('payment_status', 'paid')->sum('amount_paid'),
            'total_unpaid' => (clone $query)->where('payment_status', '!=', 'paid')->sum('total_amount'),
            'by_status' => [
                'paid' => (clone $query)->where('payment_status', 'paid')->count(),
                'unpaid' => (clone $query)->where('payment_status', 'unpaid')->count(),
                'partial' => (clone $query)->where('payment_status', 'partial')->count(),
            ],
        ];

        $bills = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'stats' => $stats,
            'bills' => $bills,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ]);
    }

    private function doctorsReport(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());

        $doctors = User::query()
            ->with(['doctorProfile', 'clinic:id,name'])
            ->where('role', 'doctor')
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->get();

        $doctorStats = [];
        foreach ($doctors as $doctor) {
            $appointments = Appointment::where('doctor_id', $doctor->id)
                ->whereBetween('date', [$fromDate, $toDate]);

            $encounters = Encounter::where('doctor_id', $doctor->id)
                ->whereBetween('encounter_date', [$fromDate, $toDate]);

            $doctorStats[] = [
                'doctor' => $doctor,
                'appointments_count' => $appointments->count(),
                'completed_appointments' => $appointments->where('status', 'completed')->count(),
                'encounters_count' => $encounters->count(),
                'revenue' => Bill::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$fromDate, $toDate])
                    ->where('payment_status', 'paid')
                    ->sum('amount_paid'),
            ];
        }

        return response()->json([
            'doctors' => $doctorStats,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
        ]);
    }

    private function summaryReport(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);
        $today = now()->toDateString();

        return response()->json([
            'today' => [
                'appointments' => Appointment::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->where('date', $today)
                    ->count(),
                'patients' => Patient::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('created_at', $today)
                    ->count(),
                'revenue' => Bill::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereDate('created_at', $today)
                    ->where('payment_status', 'paid')
                    ->sum('amount_paid'),
            ],
            'month' => [
                'appointments' => Appointment::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereMonth('date', now()->month)
                    ->count(),
                'patients' => Patient::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereMonth('created_at', now()->month)
                    ->count(),
                'encounters' => Encounter::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereMonth('encounter_date', now()->month)
                    ->count(),
                'revenue' => Bill::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->whereMonth('created_at', now()->month)
                    ->where('payment_status', 'paid')
                    ->sum('amount_paid'),
                'pending_bills' => Bill::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->where('payment_status', '!=', 'paid')
                    ->sum('total_amount'),
            ],
            'totals' => [
                'patients' => User::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->where('role', 'patient')
                    ->count(),
                'doctors' => User::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                    ->where('role', 'doctor')
                    ->count(),
                'appointments' => Appointment::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            ],
        ]);
    }

    private function exportAppointments(Request $request, string $format)
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        $appointments = Appointment::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'desc')
            ->get();

        $data = $appointments->map(fn ($a) => [
            'Date' => $a->date,
            'Time' => $a->start_time,
            'Patient' => $a->patient?->name ?? 'N/A',
            'Doctor' => $a->doctor?->name ?? 'N/A',
            'Status' => ucfirst($a->status),
            'Reason' => $a->reason ?? '',
        ]);

        return $this->downloadCsv($data->toArray(), "appointments-report-{$fromDate}-to-{$toDate}.csv");
    }

    private function exportPatients(Request $request, string $format)
    {
        $clinicId = $this->getClinicId($request);

        $patients = User::query()
            ->with(['patient', 'clinic:id,name'])
            ->where('role', 'patient')
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $patients->map(fn ($p) => [
            'Name' => $p->name,
            'Email' => $p->email,
            'Phone' => $p->phone ?? 'N/A',
            'Clinic' => $p->clinic?->name ?? 'N/A',
            'Gender' => $p->patient?->gender ?? 'N/A',
            'Blood Group' => $p->patient?->blood_group ?? 'N/A',
            'Registered' => $p->created_at->format('Y-m-d'),
        ]);

        return $this->downloadCsv($data->toArray(), 'patients-report.csv');
    }

    private function exportBilling(Request $request, string $format)
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        $bills = Bill::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $bills->map(fn ($b) => [
            'Invoice #' => $b->invoice_number,
            'Date' => $b->created_at->format('Y-m-d'),
            'Patient' => $b->patient?->name ?? 'N/A',
            'Doctor' => $b->doctor?->name ?? 'N/A',
            'Total' => $b->total_amount,
            'Paid' => $b->amount_paid ?? 0,
            'Status' => ucfirst($b->payment_status),
        ]);

        return $this->downloadCsv($data->toArray(), "billing-report-{$fromDate}-to-{$toDate}.csv");
    }

    private function downloadCsv(array $data, string $filename)
    {
        if (empty($data)) {
            $data = [['']];
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $output = fopen('php://temp', 'r+');

        if (! empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, array_values($row));
            }
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return response($content, 200, $headers);
    }

    private function getAppointmentsData(Request $request): array
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        return Appointment::query()
            ->with(['patient:id,name', 'doctor:id,name', 'service:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('date', [$fromDate, $toDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn ($a) => [
                'date' => $a->date,
                'start_time' => $a->start_time,
                'patient' => $a->patient?->name ?? '',
                'doctor' => $a->doctor?->name ?? '',
                'service' => $a->service?->name ?? '',
                'status' => $a->status,
                'reason' => $a->reason ?? '',
            ])
            ->toArray();
    }

    private function getPatientsData(Request $request): array
    {
        $clinicId = $this->getClinicId($request);

        return User::query()
            ->with(['patient', 'clinic:id,name'])
            ->where('role', 'patient')
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'email' => $p->email,
                'phone' => $p->phone ?? '',
                'gender' => $p->patient?->gender ?? '',
                'blood_group' => $p->patient?->blood_group ?? '',
                'clinic' => $p->clinic?->name ?? '',
                'created_at' => $p->created_at->format('Y-m-d'),
            ])
            ->toArray();
    }

    private function getBillingData(Request $request): array
    {
        $fromDate = $request->get('from_date', now()->startOfMonth());
        $toDate = $request->get('to_date', now()->endOfMonth());
        $clinicId = $this->getClinicId($request);

        return Bill::query()
            ->with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($b) => [
                'invoice_number' => $b->invoice_number,
                'created_at' => $b->created_at->format('Y-m-d'),
                'patient' => $b->patient?->name ?? '',
                'doctor' => $b->doctor?->name ?? '',
                'total_amount' => $b->total_amount,
                'amount_paid' => $b->amount_paid ?? 0,
                'payment_status' => $b->payment_status,
            ])
            ->toArray();
    }

    private function getClinicId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return null;
        }

        return $user->clinic_id;
    }
}
