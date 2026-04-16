<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->forUser($request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->integer('limit', 20))
            ->get();

        return response()->json($notifications);
    }

    public function unread(Request $request): JsonResponse
    {
        $count = Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }

    public function clearAll(Request $request): JsonResponse
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->delete();

        return response()->json(['message' => 'All notifications cleared']);
    }
}
