<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $patients = User::query()
            ->with(['patient:id,user_id,gender,date_of_birth,blood_group', 'clinic:id,name'])
            ->where('role', 'patient')
            ->when($request->clinic_id, fn ($q, $v) => $q->where('clinic_id', $v))
            ->when($request->search, fn ($q, $v) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$v}%")
                ->orWhere('email', 'like', "%{$v}%")
                ->orWhere('phone', 'like', "%{$v}%")))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($patients);
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
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $user = User::create([
            'clinic_id' => $validated['clinic_id'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role' => 'patient',
        ]);

        $user->patient()->create(array_merge(
            ['clinic_id' => $validated['clinic_id']],
            collect($validated)->only([
                'date_of_birth', 'gender', 'blood_group', 'address',
                'emergency_contact', 'emergency_phone', 'allergies', 'medical_history',
            ])->toArray()
        ));

        return response()->json($user->load(['patient', 'clinic']), 201);
    }

    public function show(User $patient)
    {
        $patient->load(['patient', 'clinic', 'patient.appointments']);

        return response()->json($patient);
    }

    public function update(Request $request, User $patient)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$patient->id,
            'phone' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'blood_group' => 'nullable|string|max:5',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        $patient->update([
            'name' => ($validated['first_name'] ?? $patient->first_name).' '.($validated['last_name'] ?? $patient->last_name),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        $patient->patient?->update(collect($validated)->only([
            'date_of_birth', 'gender', 'blood_group', 'address',
            'emergency_contact', 'emergency_phone', 'allergies', 'medical_history',
        ])->toArray());

        return response()->json($patient->load(['patient', 'clinic']));
    }

    public function destroy(User $patient)
    {
        if (! $patient->isPatient()) {
            return response()->json(['message' => 'User is not a patient'], 400);
        }
        $patient->delete();

        return response()->json(['message' => 'Patient deleted successfully']);
    }
}
