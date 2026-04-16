<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MedicalHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $histories = MedicalHistory::query()
            ->with(['patient:id,name', 'doctor:id,name', 'clinic:id,name'])
            ->when($clinicId, fn ($q) => $q->forClinic($clinicId))
            ->when($request->clinic_id && $request->user()->isSuperAdmin(), fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->patient_id, fn ($q, $v) => $q->forPatient($v))
            ->when($request->type, fn ($q, $v) => $q->ofType($v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('record_date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('record_date', '<=', $v))
            ->when($request->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->orderBy('record_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return response()->json($histories);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:users,id',
            'encounter_id' => 'nullable|exists:encounters,id',
            'type' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'record_date' => 'required|date',
            'severity' => 'nullable|in:normal,mild,moderate,severe',
            'metadata' => 'nullable|array',
        ]);

        $history = MedicalHistory::create([
            'clinic_id' => $clinicId,
            'doctor_id' => $validated['doctor_id'] ?? auth()->id(),
            'patient_id' => $validated['patient_id'],
            'encounter_id' => $validated['encounter_id'] ?? null,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'record_date' => $validated['record_date'],
            'severity' => $validated['severity'] ?? MedicalHistory::SEVERITY_NORMAL,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json($history->load(['patient', 'doctor', 'clinic']), 201);
    }

    public function show(MedicalHistory $medicalHistory): JsonResponse
    {
        $medicalHistory->load(['patient', 'doctor', 'clinic', 'encounter']);

        return response()->json($medicalHistory);
    }

    public function update(Request $request, MedicalHistory $medicalHistory): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|max:50',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'record_date' => 'sometimes|date',
            'severity' => 'nullable|in:normal,mild,moderate,severe',
            'metadata' => 'nullable|array',
        ]);

        $medicalHistory->update($validated);

        return response()->json($medicalHistory->load(['patient', 'doctor', 'clinic']));
    }

    public function destroy(MedicalHistory $medicalHistory): JsonResponse
    {
        $medicalHistory->delete();

        return response()->json(['message' => 'Medical history record deleted']);
    }

    public function byPatient(Request $request, int $patientId): JsonResponse
    {
        $histories = MedicalHistory::query()
            ->with(['doctor:id,name', 'clinic:id,name'])
            ->forPatient($patientId)
            ->orderBy('record_date', 'desc')
            ->get();

        return response()->json($histories);
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
