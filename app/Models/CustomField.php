<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomField extends Model
{
    protected $fillable = [
        'clinic_id',
        'entity_type',
        'field_name',
        'field_type',
        'options',
        'required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(EntityCustomFieldValue::class, 'custom_field_id');
    }

    public static function getForEntity(int $clinicId, string $entityType): Collection
    {
        return static::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->orderBy('sort_order')
            ->get();
    }

    public static function getFieldTypes(): array
    {
        return [
            'text' => 'Text',
            'number' => 'Number',
            'date' => 'Date',
            'select' => 'Dropdown',
            'textarea' => 'Long Text',
            'checkbox' => 'Checkbox',
        ];
    }
}
