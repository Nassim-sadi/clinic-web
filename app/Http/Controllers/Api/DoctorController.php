<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = User::query()
            ->with(['doctorProfile', 'clinic:id,name'])
            ->where('role', 'doctor')
            ->when($request->clinic_id, fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clinic_id' => 'required|exists:clinics,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:50',
            'specialization' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'experience' => 'nullable|string',
            'bio' => 'nullable|string',
            'consultation_fee' => 'nullable|numeric|min:0',
            'slot_duration' => 'nullable|integer|min:5|max:120',
            'is_available' => 'nullable|boolean',
        ]);

        $user = User::create([
            'clinic_id' => $validated['clinic_id'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'doctor',
        ]);

        $user->doctorProfile()->create(array_merge(
            ['clinic_id' => $validated['clinic_id']],
            collect($validated)->only([
                'title', 'specialization', 'qualifications', 'experience',
                'bio', 'consultation_fee', 'slot_duration', 'is_available',
            ])->merge([
                'consultation_fee' => $validated['consultation_fee'] ?? 0,
                'slot_duration' => $validated['slot_duration'] ?? 30,
                'is_available' => $validated['is_available'] ?? true,
            ])->toArray()
        ));

        return response()->json($user->load(['doctorProfile', 'clinic']), 201);
    }

    public function show(User $doctor)
    {
        $doctor->load(['doctorProfile', 'clinic']);

        return response()->json($doctor);
    }

    public function update(Request $request, User $doctor)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$doctor->id,
            'phone' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:50',
            'specialization' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'experience' => 'nullable|string',
            'bio' => 'nullable|string',
            'consultation_fee' => 'nullable|numeric|min:0',
            'slot_duration' => 'nullable|integer|min:5|max:120',
            'is_available' => 'nullable|boolean',
        ]);

        $doctor->update([
            'name' => ($validated['first_name'] ?? $doctor->first_name).' '.($validated['last_name'] ?? $doctor->last_name),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $doctor->doctorProfile?->update(collect($validated)->only([
            'title', 'specialization', 'qualifications', 'experience',
            'bio', 'consultation_fee', 'slot_duration', 'is_available',
        ])->toArray());

        return response()->json($doctor->load(['doctorProfile', 'clinic']));
    }

    public function destroy(User $doctor)
    {
        if (! $doctor->isDoctor()) {
            return response()->json(['message' => 'User is not a doctor'], 400);
        }
        $doctor->delete();

        return response()->json(['message' => 'Doctor deleted successfully']);
    }
}
