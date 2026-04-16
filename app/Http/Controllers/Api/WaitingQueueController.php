<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\WaitingQueue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitingQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $query = WaitingQueue::with(['patient:id,name,email,phone', 'doctor:id,name', 'appointment:id,date,start_time,end_time'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->when($request->doctor_id, fn ($q, $v) => $q->where('doctor_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v));

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $queue = $query->orderBy('priority', 'desc')
            ->orderBy('position')
            ->orderBy('checked_in_at')
            ->get();

        return response()->json($queue);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'nullable|exists:users,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'notes' => 'nullable|string|max:500',
        ]);

        $clinicId = $this->getClinicId($request);
        if (! $clinicId && $request->user()) {
            $clinicId = $request->user()->clinic_id;
        }

        if (! $clinicId) {
            return response()->json(['message' => 'Clinic ID is required'], 422);
        }

        $alreadyInQueue = WaitingQueue::where('patient_id', $validated['patient_id'])
            ->active()
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->exists();

        if ($alreadyInQueue) {
            return response()->json(['message' => 'Patient is already in queue'], 422);
        }

        $doctorId = $validated['doctor_id'] ?? null;
        $position = WaitingQueue::getNextPosition($clinicId, $doctorId);

        $queueEntry = WaitingQueue::create([
            'clinic_id' => $clinicId,
            'patient_id' => $validated['patient_id'],
            'doctor_id' => $doctorId,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'status' => WaitingQueue::STATUS_WAITING,
            'position' => $position,
            'priority' => $validated['priority'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'checked_in_at' => now(),
        ]);

        if (! empty($validated['appointment_id'])) {
            Appointment::where('id', $validated['appointment_id'])
                ->update(['status' => Appointment::STATUS_CHECKED_IN]);
        }

        return response()->json($queueEntry->load(['patient', 'doctor', 'appointment']), 201);
    }

    public function show(int $id): JsonResponse
    {
        $entry = WaitingQueue::with(['patient', 'doctor', 'appointment'])->findOrFail($id);

        return response()->json($entry);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $entry = WaitingQueue::findOrFail($id);

        $validated = $request->validate([
            'doctor_id' => 'nullable|exists:users,id',
            'priority' => 'nullable|integer|min:0|max:10',
            'notes' => 'nullable|string|max:500',
            'position' => 'nullable|integer|min:1',
        ]);

        $entry->update($validated);

        return response()->json($entry->load(['patient', 'doctor', 'appointment']));
    }

    public function destroy(int $id): JsonResponse
    {
        $entry = WaitingQueue::findOrFail($id);
        $entry->markLeft();

        return response()->json(['message' => 'Patient removed from queue']);
    }

    public function call(int $id): JsonResponse
    {
        $entry = WaitingQueue::findOrFail($id);
        $entry->call();

        return response()->json($entry->load(['patient', 'doctor', 'appointment']));
    }

    public function start(int $id): JsonResponse
    {
        $entry = WaitingQueue::findOrFail($id);
        $entry->startProgress();

        return response()->json($entry->load(['patient', 'doctor', 'appointment']));
    }

    public function complete(int $id): JsonResponse
    {
        $entry = WaitingQueue::findOrFail($id);
        $entry->complete();

        if ($entry->appointment_id) {
            Appointment::where('id', $entry->appointment_id)
                ->update(['status' => Appointment::STATUS_COMPLETED]);
        }

        return response()->json($entry->load(['patient', 'doctor', 'appointment']));
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:waiting_queue,id',
            'items.*.position' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                WaitingQueue::where('id', $item['id'])->update(['position' => $item['position']]);
            }
        });

        return response()->json(['message' => 'Queue reordered successfully']);
    }

    public function stats(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $stats = [
            'waiting' => WaitingQueue::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                ->where('status', WaitingQueue::STATUS_WAITING)
                ->count(),
            'called' => WaitingQueue::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                ->where('status', WaitingQueue::STATUS_CALLED)
                ->count(),
            'in_progress' => WaitingQueue::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                ->where('status', WaitingQueue::STATUS_IN_PROGRESS)
                ->count(),
            'completed_today' => WaitingQueue::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
                ->where('status', WaitingQueue::STATUS_COMPLETED)
                ->whereDate('updated_at', today())
                ->count(),
        ];

        return response()->json($stats);
    }

    public function display(Request $request): JsonResponse
    {
        $clinicId = $this->getClinicId($request);

        $queue = WaitingQueue::with(['patient:id,name', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->active()
            ->orderBy('status', 'desc')
            ->orderBy('priority', 'desc')
            ->orderBy('position')
            ->orderBy('checked_in_at')
            ->limit(20)
            ->get()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'position' => $entry->position,
                'patient_name' => $entry->patient?->name,
                'doctor_name' => $entry->doctor?->name,
                'status' => $entry->status,
                'wait_time' => $entry->checked_in_at ? $entry->checked_in_at->diffInMinutes(now()) : 0,
                'called_at' => $entry->called_at,
            ]);

        return response()->json($queue);
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
