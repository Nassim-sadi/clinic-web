<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GoogleCalendarService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private string $accessToken;

    private string $refreshToken;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id');
        $this->clientSecret = config('services.google.client_secret');
        $this->redirectUri = config('services.google.redirect_uri');
    }

    public function setAccessToken(string $accessToken, ?string $refreshToken = null): void
    {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->clientId) && ! empty($this->clientSecret);
    }

    public function getAuthUrl(string $state = ''): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    public function exchangeCodeForTokens(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to exchange code for tokens: '.$response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(): ?string
    {
        if (! $this->refreshToken) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    public function listCalendars(): array
    {
        $response = Http::withToken($this->accessToken)
            ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList');

        if ($response->failed()) {
            throw new \Exception('Failed to list calendars');
        }

        return $response->json()['items'] ?? [];
    }

    public function createEvent(Appointment $appointment, ?string $calendarId = 'primary'): array
    {
        $clinic = $appointment->clinic;
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $service = $appointment->service;

        $event = [
            'summary' => $service ? "{$service->name} - {$patient?->name}" : "Appointment - {$patient?->name}",
            'description' => $this->buildDescription($appointment),
            'start' => [
                'dateTime' => "{$appointment->date}T{$appointment->start_time}:00",
                'timeZone' => $clinic?->timezone ?? config('app.timezone'),
            ],
            'end' => [
                'dateTime' => "{$appointment->date}T{$appointment->end_time}:00",
                'timeZone' => $clinic?->timezone ?? config('app.timezone'),
            ],
            'attendees' => [
                ['email' => $patient?->email],
            ],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 60],
                    ['method' => 'popup', 'minutes' => 30],
                ],
            ],
        ];

        if ($doctor?->email) {
            $event['attendees'][] = ['email' => $doctor->email];
        }

        $response = Http::withToken($this->accessToken)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events", $event);

        if ($response->failed()) {
            throw new \Exception('Failed to create calendar event');
        }

        return $response->json();
    }

    public function updateEvent(string $eventId, Appointment $appointment, string $calendarId = 'primary'): array
    {
        $patient = $appointment->patient;
        $service = $appointment->service;
        $clinic = $appointment->clinic;

        $event = [
            'summary' => $service ? "{$service->name} - {$patient?->name}" : "Appointment - {$patient?->name}",
            'description' => $this->buildDescription($appointment),
            'start' => [
                'dateTime' => "{$appointment->date}T{$appointment->start_time}:00",
                'timeZone' => $clinic?->timezone ?? config('app.timezone'),
            ],
            'end' => [
                'dateTime' => "{$appointment->date}T{$appointment->end_time}:00",
                'timeZone' => $clinic?->timezone ?? config('app.timezone'),
            ],
        ];

        $response = Http::withToken($this->accessToken)
            ->put("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$eventId}", $event);

        if ($response->failed()) {
            throw new \Exception('Failed to update calendar event');
        }

        return $response->json();
    }

    public function deleteEvent(string $eventId, string $calendarId = 'primary'): bool
    {
        $response = Http::withToken($this->accessToken)
            ->delete("https://www.googleapis.com/calendar/v3/calendars/{$calendarId}/events/{$eventId}");

        return $response->successful();
    }

    public function syncAppointment(Appointment $appointment): ?array
    {
        if (! $appointment->google_event_id) {
            return $this->createEvent($appointment);
        }

        if ($appointment->status === 'cancelled') {
            $this->deleteEvent($appointment->google_event_id);

            return null;
        }

        return $this->updateEvent($appointment->google_event_id, $appointment);
    }

    private function buildDescription(Appointment $appointment): string
    {
        $parts = [];

        if ($appointment->doctor) {
            $parts[] = "Doctor: {$appointment->doctor->name}";
        }

        if ($appointment->service) {
            $parts[] = "Service: {$appointment->service->name}";
        }

        if ($appointment->reason) {
            $parts[] = "Reason: {$appointment->reason}";
        }

        if ($appointment->clinic) {
            $parts[] = "Clinic: {$appointment->clinic->name}";
        }

        return implode("\n", $parts);
    }

    public function storeTokens(int $clinicId, string $accessToken, ?string $refreshToken, ?string $calendarId = null): void
    {
        Cache::put("google_tokens_{$clinicId}", [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'calendar_id' => $calendarId,
            'expires_at' => now()->addHour()->timestamp,
        ], now()->addHour());

        if ($refreshToken) {
            Cache::put("google_refresh_{$clinicId}", $refreshToken, now()->addYear());
        }
    }

    public function getStoredTokens(int $clinicId): ?array
    {
        return Cache::get("google_tokens_{$clinicId}");
    }
}
