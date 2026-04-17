<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $query = Activity::with('causer')
            ->whereHas('causer', function ($q) use ($request) {
                $q->where('id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%'.$request->search.'%')
                    ->orWhere('log_name', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        $activities = $query->paginate($perPage);

        return response()->json($activities);
    }

    public function logNames()
    {
        $logNames = Activity::distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name')
            ->filter()
            ->values();

        return response()->json($logNames);
    }
}
