<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalHistory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'doctor_id',
        'encounter_id',
        'type',
        'title',
        'description',
        'record_date',
        'severity',
        'metadata',
    ];

    protected $casts = [
        'record_date' => 'date',
        'metadata' => 'array',
    ];

    const TYPE_ALLERGY = 'allergy';

    const TYPE_MEDICATION = 'medication';

    const TYPE_SURGERY = 'surgery';

    const TYPE_CONDITION = 'condition';

    const TYPE_FAMILY_HISTORY = 'family_history';

    const TYPE_LAB_RESULT = 'lab_result';

    const TYPE_IMAGING = 'imaging';

    const TYPE_PROCEDURE = 'procedure';

    const TYPE_IMMUNIZATION = 'immunization';

    const TYPE_VITAL_SIGNS = 'vital_signs';

    const TYPE_GENERAL = 'general';

    const SEVERITY_NORMAL = 'normal';

    const SEVERITY_MILD = 'mild';

    const SEVERITY_MODERATE = 'moderate';

    const SEVERITY_SEVERE = 'severe';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function scopeForClinic($query, ?int $clinicId)
    {
        return $clinicId ? $query->where('clinic_id', $clinicId) : $query;
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForPatient($query, int $patientId)
    {
        return $query->where('patient_id', $patientId);
    }
}
