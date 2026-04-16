<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public const TYPE_PATIENT_CREATED = 'patient_created';

    public const TYPE_APPOINTMENT_CREATED = 'appointment_created';

    public const TYPE_APPOINTMENT_UPDATED = 'appointment_updated';

    public const TYPE_APPOINTMENT_CANCELLED = 'appointment_cancelled';

    public const TYPE_DOCTOR_STATUS_CHANGED = 'doctor_status_changed';

    public const TYPE_ENCOUNTER_CREATED = 'encounter_created';

    public const TYPE_BILL_CREATED = 'bill_created';

    public const TYPE_BILL_PAID = 'bill_paid';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function createNotification(int $userId, string $type, string $title, string $message, array $data = [])
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function notifyAdmins(string $type, string $title, string $message, array $data = [])
    {
        $admins = User::whereIn('role', ['super_admin', 'clinic_admin'])->get();
        foreach ($admins as $admin) {
            static::createNotification($admin->id, $type, $title, $message, $data);
        }
    }

    public static function notifyDoctor(int $doctorId, string $type, string $title, string $message, array $data = [])
    {
        static::createNotification($doctorId, $type, $title, $message, $data);
    }
}
