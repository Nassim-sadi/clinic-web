<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encounter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EncounterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $encounters = Encounter::query()
            ->with(['patient:id,name,clinic_id', 'doctor:id,name', 'clinic:id,name', 'appointment'])
            ->when($clinicId, fn ($q) => $q->forClinic($clinicId))
            ->when($request->clinic_id && $request->user()->isSuperAdmin(), fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id, fn ($q, $v) => $q->where('patient_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('encounter_date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('encounter_date', '<=', $v))
            ->when($request->search, fn ($q, $v) => $q->whereHas('patient', fn ($pq) => $pq->where('name', 'like', "%{$v}%")))
            ->orderBy('encounter_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($encounters);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'encounter_date' => 'required|date',
            'type' => 'nullable|string',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'examination' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        $encounter = Encounter::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $validated['doctor_id'],
            'patient_id' => $validated['patient_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'encounter_date' => $validated['encounter_date'],
            'type' => $validated['type'] ?? 'consultation',
            'status' => Encounter::STATUS_PENDING,
            'chief_complaint' => $validated['chief_complaint'] ?? null,
            'diagnosis' => $validated['diagnosis'] ?? null,
            'examination' => $validated['examination'] ?? null,
            'treatment' => $validated['treatment'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'doctor_notes' => $validated['doctor_notes'] ?? null,
            'added_by' => $request->user()->id,
        ]);

        $encounter->load(['patient:id,name', 'doctor:id,name']);

        return response()->json($encounter->load(['patient', 'doctor', 'clinic']), 201);
    }

    public function show(Encounter $encounter): JsonResponse
    {
        $encounter->load(['patient', 'doctor', 'clinic', 'appointment', 'prescriptions', 'bill']);

        return response()->json($encounter);
    }

    public function update(Request $request, Encounter $encounter): JsonResponse
    {
        $validated = $request->validate([
            'encounter_date' => 'sometimes|date',
            'type' => 'sometimes|string',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'chief_complaint' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'examination' => 'nullable|string',
            'treatment' => 'nullable|string',
            'notes' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        $encounter->update($validated);

        return response()->json($encounter->load(['patient', 'doctor', 'clinic']));
    }

    public function destroy(Encounter $encounter): JsonResponse
    {
        $encounter->update(['status' => Encounter::STATUS_CANCELLED]);

        return response()->json(['message' => 'Encounter cancelled successfully']);
    }

    public function complete(Request $request, Encounter $encounter): JsonResponse
    {
        $validated = $request->validate([
            'diagnosis' => 'nullable|string',
            'treatment' => 'nullable|string',
            'doctor_notes' => 'nullable|string',
        ]);

        $encounter->update([
            'status' => Encounter::STATUS_COMPLETED,
            ...$validated,
        ]);

        return response()->json($encounter->load(['patient', 'doctor', 'clinic']));
    }

    public function byPatient(Request $request, int $patientId): JsonResponse
    {
        $encounters = Encounter::query()
            ->with(['doctor:id,name', 'clinic:id,name'])
            ->where('patient_id', $patientId)
            ->orderBy('encounter_date', 'desc')
            ->get();

        return response()->json($encounters);
    }

    public function byAppointment(Request $request, int $appointmentId): JsonResponse
    {
        $encounter = Encounter::query()
            ->with(['patient', 'doctor:id,name', 'clinic:id,name', 'prescriptions', 'bill'])
            ->where('appointment_id', $appointmentId)
            ->first();

        if (! $encounter) {
            return response()->json(['message' => 'No encounter found for this appointment'], 404);
        }

        return response()->json($encounter);
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
