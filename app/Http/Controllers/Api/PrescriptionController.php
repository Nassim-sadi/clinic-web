<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $prescriptions = Prescription::query()
            ->with(['patient:id,name', 'doctor:id,name', 'encounter'])
            ->when($clinicId, fn ($q) => $q->whereHas('encounter', fn ($eq) => $eq->where('clinic_id', $clinicId)))
            ->when($request->patient_id, fn ($q, $v) => $q->where('patient_id', $v))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->encounter_id, fn ($q, $v) => $q->where('encounter_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($prescriptions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encounter_id' => 'required|exists:encounters,id',
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'medicine' => 'required|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $prescription = Prescription::create([
            'encounter_id' => $validated['encounter_id'],
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'medicine' => $validated['medicine'],
            'dosage' => $validated['dosage'] ?? null,
            'frequency' => $validated['frequency'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'instructions' => $validated['instructions'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => Prescription::STATUS_ACTIVE,
        ]);

        return response()->json($prescription->load(['patient', 'doctor', 'encounter']), 201);
    }

    public function show(Prescription $prescription): JsonResponse
    {
        $prescription->load(['patient', 'doctor', 'encounter']);

        return response()->json($prescription);
    }

    public function update(Request $request, Prescription $prescription): JsonResponse
    {
        $validated = $request->validate([
            'medicine' => 'sometimes|string',
            'dosage' => 'nullable|string',
            'frequency' => 'nullable|string',
            'duration' => 'nullable|string',
            'instructions' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:active,completed,cancelled',
            'is_active' => 'sometimes|boolean',
        ]);

        $prescription->update($validated);

        return response()->json($prescription->load(['patient', 'doctor', 'encounter']));
    }

    public function destroy(Prescription $prescription): JsonResponse
    {
        $prescription->update(['status' => Prescription::STATUS_CANCELLED]);

        return response()->json(['message' => 'Prescription cancelled successfully']);
    }

    public function byPatient(Request $request, int $patientId): JsonResponse
    {
        $prescriptions = Prescription::query()
            ->with(['doctor:id,name', 'encounter'])
            ->where('patient_id', $patientId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prescriptions);
    }

    public function byEncounter(Request $request, int $encounterId): JsonResponse
    {
        $prescriptions = Prescription::query()
            ->with(['doctor:id,name'])
            ->where('encounter_id', $encounterId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($prescriptions);
    }

    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'encounter_id' => 'nullable|exists:encounters,id',
        ]);

        $query = Prescription::query()
            ->with(['patient:id,name', 'doctor:id,name', 'encounter:id,encounter_date']);

        if ($request->patient_id) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->encounter_id) {
            $query->where('encounter_id', $request->encounter_id);
        }

        $prescriptions = $query->get();

        $data = $prescriptions->map(fn ($p) => [
            'patient' => $p->patient?->name,
            'doctor' => $p->doctor?->name,
            'medicine' => $p->medicine,
            'dosage' => $p->dosage,
            'frequency' => $p->frequency,
            'duration' => $p->duration,
            'instructions' => $p->instructions,
            'date' => $p->created_at?->format('Y-m-d'),
        ]);

        return response()->json(['prescriptions' => $data]);
    }

    public function downloadPdf(Prescription $prescription)
    {
        $prescription->load(['patient.user', 'doctor.doctorProfile', 'encounter']);

        $pdf = app(PdfService::class)->generatePrescription($prescription);

        return $pdf;
    }

    public function sendEmail(Request $request, Prescription $prescription): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email',
        ]);

        $email = $request->email ?? $prescription->patient?->email;

        if (! $email) {
            return response()->json(['message' => 'No email address available'], 422);
        }

        $sent = app(EmailService::class)->sendPrescription($prescription->id, $email);

        if ($sent) {
            return response()->json(['message' => 'Prescription sent successfully']);
        }

        return response()->json(['message' => 'Failed to send email'], 500);
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
