<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clinic_id',
        'title',
        'specialization',
        'qualifications',
        'experience',
        'bio',
        'consultation_fee',
        'slot_duration',
        'is_available',
        'available_days',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'consultation_fee' => 'decimal:2',
        'slot_duration' => 'integer',
        'is_available' => 'boolean',
        'available_days' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'doctor_services')
            ->withPivot('custom_price', 'custom_duration', 'is_active')
            ->withTimestamps();
    }

    public function sessions()
    {
        return $this->hasMany(DoctorSession::class);
    }
}
