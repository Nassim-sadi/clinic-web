<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Bill;
use App\Models\Clinic;
use App\Models\Encounter;
use App\Models\User;
use App\Support\TierConfig;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();
        $tier = TierConfig::getTier();

        $clinicId = $user->isSuperAdmin() ? null : $user->clinic_id;

        $stats = [
            'patients' => User::where('role', 'patient')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            'doctors' => User::where('role', 'doctor')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            'today_appointments' => Appointment::whereDate('date', now()->toDateString())->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            'pending_appointments' => Appointment::where('status', 'pending')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            'completed_today' => Appointment::whereDate('date', now()->toDateString())->where('status', 'completed')->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count(),
            'revenue' => Bill::when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->sum('amount_paid'),
            'clinics' => $tier !== 'one_doctor' ? Clinic::count() : 1,
        ];

        if (TierConfig::hasFeature('encounters')) {
            $stats['pending_encounters'] = Encounter::whereIn('status', ['pending', 'in_progress'])->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))->count();
        }

        return response()->json($stats);
    }

    public function recentAppointments(Request $request)
    {
        $user = $request->user();
        $clinicId = $user->isSuperAdmin() ? null : $user->clinic_id;

        $appointments = Appointment::query()
            ->with(['patient:id,name,clinic_id', 'doctor:id,name'])
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->latest('date')
            ->latest('start_time')
            ->limit(10)
            ->get();

        return response()->json($appointments);
    }

    public function appointmentStats(Request $request)
    {
        $user = $request->user();
        $clinicId = $user->isSuperAdmin() ? null : $user->clinic_id;

        $stats = Appointment::query()
            ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show,
                COUNT(*) as total
            ")
            ->when($clinicId, fn ($q) => $q->where('clinic_id', $clinicId))
            ->first();

        $total = $stats->total ?: 1;
        $data = [
            ['label' => 'Pending', 'value' => $stats->pending ?: 0, 'percent' => round(($stats->pending ?: 0) / $total * 100), 'color' => 'warning'],
            ['label' => 'Confirmed', 'value' => $stats->confirmed ?: 0, 'percent' => round(($stats->confirmed ?: 0) / $total * 100), 'color' => 'info'],
            ['label' => 'Completed', 'value' => $stats->completed ?: 0, 'percent' => round(($stats->completed ?: 0) / $total * 100), 'color' => 'success'],
            ['label' => 'Cancelled', 'value' => $stats->cancelled ?: 0, 'percent' => round(($stats->cancelled ?: 0) / $total * 100), 'color' => 'error'],
        ];

        return response()->json($data);
    }
}
