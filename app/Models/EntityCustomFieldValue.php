<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityCustomFieldValue extends Model
{
    protected $table = 'entity_custom_field_values';

    protected $fillable = [
        'custom_field_id',
        'entity_id',
        'value',
    ];

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    public static function setValue(int $customFieldId, int $entityId, ?string $value): void
    {
        static::updateOrCreate(
            ['custom_field_id' => $customFieldId, 'entity_id' => $entityId],
            ['value' => $value]
        );
    }

    public static function getValuesForEntity(string $entityType, int $entityId, ?int $clinicId = null): array
    {
        if (! $clinicId) {
            $clinicId = auth()->user()?->clinic_id ?? session('active_clinic');
        }

        $fields = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->with('values')
            ->get();

        $values = [];
        foreach ($fields as $field) {
            $value = $field->values->firstWhere('entity_id', $entityId);
            $values[$field->field_name] = $value?->value;
        }

        return $values;
    }

    public static function saveValuesForEntity(string $entityType, int $entityId, array $data, ?int $clinicId = null): void
    {
        if (! $clinicId) {
            $clinicId = auth()->user()?->clinic_id ?? session('active_clinic');
        }

        $fields = CustomField::where('clinic_id', $clinicId)
            ->where('entity_type', $entityType)
            ->get();

        foreach ($fields as $field) {
            if (isset($data[$field->field_name])) {
                static::setValue($field->id, $entityId, $data[$field->field_name]);
            }
        }
    }
}
