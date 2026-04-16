<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorSession;
use App\Models\User;
use Carbon\Carbon;

class AppointmentService
{
    public function __construct(
        private readonly User $doctor,
        private readonly Carbon $date
    ) {}

    public function getAvailableSlots(): array
    {
        $slotDuration = $this->doctor->doctorProfile->slot_duration ?? 30;
        $session = $this->getDoctorSession();

        if (! $session) {
            return [];
        }

        $bookedSlots = $this->getBookedAppointments();

        return $this->generateSlots($session, $slotDuration, $bookedSlots);
    }

    public function hasOverlappingAppointment(string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        return Appointment::active()
            ->forDoctor($this->doctor->id)
            ->onDate($this->date->toDateString())
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where(fn ($q) => $q->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime))
            ->exists();
    }

    public function canBookSlot(string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        if ($this->hasOverlappingAppointment($startTime, $endTime, $excludeId)) {
            return false;
        }

        if (! $this->isWithinSession($startTime, $endTime)) {
            return false;
        }

        return true;
    }

    private function getDoctorSession(): ?DoctorSession
    {
        return DoctorSession::where('doctor_id', $this->doctor->id)
            ->where('day_of_week', $this->date->dayOfWeek)
            ->where('is_active', true)
            ->first();
    }

    private function getBookedAppointments(): array
    {
        return Appointment::active()
            ->forDoctor($this->doctor->id)
            ->onDate($this->date->toDateString())
            ->get(['start_time', 'end_time'])
            ->toArray();
    }

    private function generateSlots(DoctorSession $session, int $slotDuration, array $bookedSlots): array
    {
        $slots = [];
        $sessionStart = Carbon::parse($session->start_time, 'UTC');
        $sessionEnd = Carbon::parse($session->end_time, 'UTC');

        $current = $sessionStart->copy();

        while ($current->copy()->addMinutes($slotDuration)->lte($sessionEnd)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($slotDuration)->format('H:i');

            if (! $this->slotOverlapsAnyBooked($slotStart, $slotEnd, $bookedSlots)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ];
            }

            $current->addMinutes($slotDuration);
        }

        return $slots;
    }

    private function slotOverlapsAnyBooked(string $slotStart, string $slotEnd, array $bookedSlots): bool
    {
        $slotStartCarbon = Carbon::parse($slotStart, 'UTC');
        $slotEndCarbon = Carbon::parse($slotEnd, 'UTC');

        foreach ($bookedSlots as $booked) {
            $bookedStart = Carbon::parse($booked['start_time'], 'UTC');
            $bookedEnd = Carbon::parse($booked['end_time'], 'UTC');

            if ($slotStartCarbon->lt($bookedEnd) && $slotEndCarbon->gt($bookedStart)) {
                return true;
            }
        }

        return false;
    }

    private function isWithinSession(string $startTime, string $endTime): bool
    {
        $session = $this->getDoctorSession();

        if (! $session) {
            return false;
        }

        $slotStart = Carbon::parse($startTime, 'UTC');
        $slotEnd = Carbon::parse($endTime, 'UTC');
        $sessionStart = Carbon::parse($session->start_time, 'UTC');
        $sessionEnd = Carbon::parse($session->end_time, 'UTC');

        return $slotStart->gte($sessionStart) && $slotEnd->lte($sessionEnd);
    }
}
