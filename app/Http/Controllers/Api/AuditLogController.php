<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'action' => 'nullable|in:created,updated,deleted,restored',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $query = AuditLog::with('user:id,name,email')
            ->when($request->entity_type, fn ($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->entity_id, fn ($q) => $q->where('entity_id', $request->entity_id))
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn ($q) => $q->where('action', $request->action))
            ->when($request->from_date, fn ($q) => $q->where('created_at', '>=', $request->from_date))
            ->when($request->to_date, fn ($q) => $q->where('created_at', '<=', $request->to_date.' 23:59:59'));

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->integer('per_page', 50));

        return response()->json($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = AuditLog::with('user:id,name,email')->findOrFail($id);

        return response()->json($log);
    }

    public function entityHistory(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $logs = AuditLog::with('user:id,name,email')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
}
