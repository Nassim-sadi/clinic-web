<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitingQueue extends Model
{
    protected $table = 'waiting_queue';

    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'status',
        'position',
        'priority',
        'notes',
        'checked_in_at',
        'called_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'called_at' => 'datetime',
        'priority' => 'integer',
        'position' => 'integer',
    ];

    public const STATUS_WAITING = 'waiting';

    public const STATUS_CALLED = 'called';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LEFT = 'left';

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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public static function getNextPosition(int $clinicId, ?int $doctorId = null): int
    {
        $query = static::where('clinic_id', $clinicId)
            ->where('status', '!=', self::STATUS_COMPLETED)
            ->where('status', '!=', self::STATUS_LEFT);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        return ($query->max('position') ?? 0) + 1;
    }

    public function call(): void
    {
        $this->update([
            'status' => self::STATUS_CALLED,
            'called_at' => now(),
        ]);
    }

    public function startProgress(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    public function markLeft(): void
    {
        $this->update([
            'status' => self::STATUS_LEFT,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_LEFT]);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeForDoctor($query, int $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForClinic($query, int $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }
}
