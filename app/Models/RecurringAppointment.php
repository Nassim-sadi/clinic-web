<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringAppointment extends Model
{
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'service_id',
        'recurrence_rule',
        'start_date',
        'end_date',
        'max_occurrences',
        'start_time',
        'end_time',
        'reason',
        'exceptions',
        'is_active',
    ];

    protected $casts = [
        'exceptions' => 'array',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'recurring_appointment_id');
    }

    public function generateOccurrences(int $count = 100): array
    {
        $dates = [];
        $exceptions = $this->exceptions ?? [];
        $startDate = Carbon::parse($this->start_date);
        $endDate = $this->end_date ? Carbon::parse($this->end_date) : $startDate->copy()->addYear();

        $rule = $this->parseRecurrenceRule($this->recurrence_rule);

        $current = $startDate->copy();
        $generated = 0;

        while ($current->lte($endDate) && $generated < $count) {
            if ($this->shouldOccurOnDate($current, $rule) && ! in_array($current->format('Y-m-d'), $exceptions)) {
                $dates[] = [
                    'date' => $current->format('Y-m-d'),
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                ];
                $generated++;
            }

            $current->addDay();
        }

        return $dates;
    }

    private function parseRecurrenceRule(string $rule): array
    {
        $parts = explode(';', strtolower($rule));
        $parsed = [];

        foreach ($parts as $part) {
            $keyValue = explode('=', $part);
            if (count($keyValue) === 2) {
                $parsed[$keyValue[0]] = $keyValue[1];
            }
        }

        return $parsed;
    }

    private function shouldOccurOnDate(Carbon $date, array $rule): bool
    {
        $freq = $rule['freq'] ?? 'daily';

        return match ($freq) {
            'daily' => true,
            'weekly' => $this->checkWeeklyRule($date, $rule),
            'monthly' => $this->checkMonthlyRule($date, $rule),
            default => true,
        };
    }

    private function checkWeeklyRule(Carbon $date, array $rule): bool
    {
        $byDay = $rule['byday'] ?? '';
        if (empty($byDay)) {
            return true;
        }

        $dayMap = [
            'su' => 0, 'mo' => 1, 'tu' => 2, 'we' => 3,
            'th' => 4, 'fr' => 5, 'sa' => 6,
        ];

        $weekday = strtolower($date->format('D'));
        $weekdayCode = array_search($date->dayOfWeek, $dayMap) ?: $weekday;

        return str_contains(strtolower($byDay), $weekdayCode);
    }

    private function checkMonthlyRule(Carbon $date, array $rule): bool
    {
        $interval = isset($rule['interval']) ? (int) $rule['interval'] : 1;

        $monthsDiff = $date->month - Carbon::parse($this->start_date)->month;
        $yearsDiff = $date->year - Carbon::parse($this->start_date)->year;

        return (($yearsDiff * 12 + $monthsDiff) % $interval) === 0;
    }

    public static function buildRule(string $frequency, ?int $interval = null, ?string $byDay = null): string
    {
        $rule = "freq={$frequency}";

        if ($interval) {
            $rule .= ";interval={$interval}";
        }

        if ($byDay) {
            $rule .= ";byday={$byDay}";
        }

        return $rule;
    }

    public function cancelOccurrence(string $date): void
    {
        $exceptions = $this->exceptions ?? [];
        $exceptions[] = $date;
        $this->exceptions = array_unique($exceptions);
        $this->save();
    }

    public function pause(): void
    {
        $this->update(['is_active' => false]);
    }

    public function resume(): void
    {
        $this->update(['is_active' => true]);
    }
}
