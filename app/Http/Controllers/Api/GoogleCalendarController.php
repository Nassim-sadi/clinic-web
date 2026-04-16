<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\GoogleCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GoogleCalendarController extends Controller
{
    public function __construct(private GoogleCalendarService $calendarService) {}

    public function isConfigured(): JsonResponse
    {
        return response()->json([
            'configured' => $this->calendarService->isConfigured(),
        ]);
    }

    public function authUrl(Request $request): JsonResponse
    {
        $state = base64_encode(json_encode([
            'clinic_id' => $request->user()->clinic_id,
            'user_id' => $request->user()->id,
        ]));

        return response()->json([
            'url' => $this->calendarService->getAuthUrl($state),
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
        ]);

        try {
            $tokens = $this->calendarService->exchangeCodeForTokens($request->code);
            $state = json_decode(base64_decode($request->state), true);
            $clinicId = $state['clinic_id'] ?? null;

            if (! $clinicId) {
                return response()->json(['message' => 'Invalid state'], 400);
            }

            $this->calendarService->storeTokens(
                $clinicId,
                $tokens['access_token'],
                $tokens['refresh_token'] ?? null
            );

            return response()->json([
                'message' => 'Google Calendar connected successfully',
                'access_token' => $tokens['access_token'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to connect Google Calendar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listCalendars(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        $tokens = $this->calendarService->getStoredTokens($clinicId);

        if (! $tokens) {
            return response()->json(['message' => 'Not connected'], 401);
        }

        try {
            $this->calendarService->setAccessToken($tokens['access_token'], $tokens['refresh_token'] ?? null);
            $calendars = $this->calendarService->listCalendars();

            return response()->json($calendars);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch calendars',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncAppointment(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'calendar_id' => 'nullable|string',
        ]);

        $clinicId = $request->user()->clinic_id;
        $tokens = $this->calendarService->getStoredTokens($clinicId);

        if (! $tokens) {
            return response()->json(['message' => 'Not connected to Google Calendar'], 401);
        }

        try {
            $this->calendarService->setAccessToken($tokens['access_token'], $tokens['refresh_token'] ?? null);

            $appointment = Appointment::findOrFail($request->appointment_id);
            $event = $this->calendarService->syncAppointment($appointment);

            if ($event) {
                $appointment->update([
                    'google_event_id' => $event['id'],
                    'google_calendar_id' => $request->calendar_id ?? $tokens['calendar_id'] ?? 'primary',
                ]);
            }

            return response()->json([
                'message' => 'Appointment synced successfully',
                'event' => $event,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function disconnect(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;

        Cache::forget("google_tokens_{$clinicId}");
        Cache::forget("google_refresh_{$clinicId}");

        return response()->json(['message' => 'Google Calendar disconnected']);
    }
}
