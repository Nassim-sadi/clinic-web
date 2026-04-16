<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\RecurringAppointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecurringAppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $query = RecurringAppointment::with(['patient:id,name', 'doctor:id,name', 'service:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->patient_id, fn ($q, $v) => $q->where('patient_id', $v))
            ->when(isset($request->is_active), fn ($q, $v) => $q->where('is_active', $v));

        $recurring = $query->orderBy('created_at', 'desc')->get();

        return response()->json($recurring);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:users,id',
            'service_id' => 'nullable|exists:services,id',
            'recurrence_rule' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'max_occurrences' => 'nullable|integer|min:1|max:52',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'nullable|string|max:500',
        ]);

        $clinicId = $this->getClinicId($request);
        if (! $clinicId && $request->user()) {
            $clinicId = $request->user()->clinic_id;
        }

        $recurring = RecurringAppointment::create([
            ...$validated,
            'clinic_id' => $clinicId,
        ]);

        return response()->json($recurring->load(['patient', 'doctor', 'service']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $recurring = RecurringAppointment::with(['patient', 'doctor', 'service'])->findOrFail($id);

        return response()->json($recurring);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);

        $validated = $request->validate([
            'recurrence_rule' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'max_occurrences' => 'nullable|integer|min:1|max:52',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        $recurring->update($validated);

        return response()->json($recurring->load(['patient', 'doctor', 'service']));
    }

    public function destroy(int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $recurring->delete();

        return response()->json(['message' => 'Recurring appointment deleted successfully']);
    }

    public function generateInstances(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $count = $request->integer('count', 10);

        $instances = $recurring->generateOccurrences($count);

        return response()->json([
            'recurring' => $recurring,
            'instances' => $instances,
        ]);
    }

    public function createAppointments(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $count = $request->integer('count', 5);

        $existingDates = Appointment::where('recurring_appointment_id', $id)
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->toArray();

        $occurrences = $recurring->generateOccurrences($count * 2);
        $toCreate = [];

        foreach ($occurrences as $occurrence) {
            if (! in_array($occurrence['date'], $existingDates)) {
                $toCreate[] = $occurrence;
                if (count($toCreate) >= $count) {
                    break;
                }
            }
        }

        $created = [];
        foreach ($toCreate as $occurrence) {
            $appointment = Appointment::create([
                'clinic_id' => $recurring->clinic_id,
                'patient_id' => $recurring->patient_id,
                'doctor_id' => $recurring->doctor_id,
                'service_id' => $recurring->service_id,
                'date' => $occurrence['date'],
                'start_time' => $occurrence['start_time'],
                'end_time' => $occurrence['end_time'],
                'status' => 'confirmed',
                'reason' => $recurring->reason,
                'recurring_appointment_id' => $recurring->id,
            ]);
            $created[] = $appointment;
        }

        return response()->json([
            'message' => count($created).' appointments created',
            'appointments' => $created,
        ]);
    }

    public function cancelOccurrence(Request $request, int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $recurring->cancelOccurrence($request->date);

        return response()->json(['message' => 'Occurrence cancelled']);
    }

    public function pause(int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $recurring->pause();

        return response()->json(['message' => 'Recurring appointment paused']);
    }

    public function resume(int $id): JsonResponse
    {
        $recurring = RecurringAppointment::findOrFail($id);
        $recurring->resume();

        return response()->json(['message' => 'Recurring appointment resumed']);
    }

    private function getClinicId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return null;
        }

        return $user?->clinic_id;
    }
}
