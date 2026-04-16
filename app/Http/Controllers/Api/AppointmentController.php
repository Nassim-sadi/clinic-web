<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $appointments = Appointment::query()
            ->with(['patient:id,name,clinic_id', 'doctor:id,name', 'service:id,name'])
            ->when($clinicId, fn ($q) => $q->forClinic($clinicId))
            ->when($request->clinic_id && $request->user()->isSuperAdmin(), fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id, fn ($q, $v) => $q->where('patient_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->date, fn ($q, $v) => $q->whereDate('date', $v))
            ->when($request->from_date, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to_date, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->latest('date')
            ->latest('start_time')
            ->paginate($request->integer('per_page', 15));

        return response()->json($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:users,id',
            'service_id' => 'nullable|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $doctor = User::with('doctorProfile')->findOrFail($validated['doctor_id']);

        if (! $doctor->doctorProfile) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }

        $date = Carbon::parse($validated['date']);
        $service = new AppointmentService($doctor, $date);

        if (! $service->canBookSlot($validated['start_time'], $validated['end_time'])) {
            return response()->json(['message' => 'This time slot conflicts with an existing appointment or is outside the doctor\'s available hours'], 422);
        }

        $appointment = Appointment::create([
            'clinic_id' => $clinicId,
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $validated['doctor_id'],
            'service_id' => $validated['service_id'] ?? null,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'amount' => $validated['amount'] ?? 0,
            'status' => Appointment::STATUS_PENDING,
            'payment_status' => 'pending',
        ]);

        $appointment->load(['patient', 'doctor', 'service']);

        return response()->json($appointment, 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        $appointment->load(['patient.user', 'doctor', 'service', 'clinic', 'encounter']);

        return response()->json($appointment);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'status' => 'sometimes|in:pending,confirmed,checked_in,in_progress,completed,cancelled,no_show',
            'payment_status' => 'sometimes|in:pending,paid,refunded',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'amount' => 'nullable|numeric|min:0',
        ]);

        if (isset($validated['start_time']) || isset($validated['end_time']) || isset($validated['date'])) {
            $doctor = $appointment->doctor;
            $date = isset($validated['date']) ? Carbon::parse($validated['date']) : $appointment->date;
            $startTime = $validated['start_time'] ?? $appointment->start_time;
            $endTime = $validated['end_time'] ?? $appointment->end_time;

            $service = new AppointmentService($doctor, $date);

            if (! $service->canBookSlot($startTime, $endTime, $appointment->id)) {
                return response()->json(['message' => 'This time slot conflicts with an existing appointment'], 422);
            }
        }

        $appointment->update($validated);
        $appointment->load(['patient', 'doctor', 'service']);

        return response()->json($appointment);
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->update(['status' => Appointment::STATUS_CANCELLED]);

        return response()->json(['message' => 'Appointment cancelled successfully']);
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $doctor = User::with('doctorProfile')->findOrFail($validated['doctor_id']);

        if (! $doctor->doctorProfile) {
            return response()->json(['slots' => [], 'message' => 'Doctor not found'], 404);
        }

        $date = Carbon::parse($validated['date']);
        $service = new AppointmentService($doctor, $date);

        $slots = $service->getAvailableSlots();
        $slotDuration = $doctor->doctorProfile->slot_duration ?? 30;

        if (empty($slots)) {
            return response()->json(['slots' => [], 'message' => 'No availability on this day']);
        }

        return response()->json(['slots' => $slots, 'slot_duration' => $slotDuration]);
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
