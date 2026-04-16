<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction(string $action, string $entityType, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logCreated(string $entityType, int $entityId, array $newValues): self
    {
        return static::logAction('created', $entityType, $entityId, null, $newValues);
    }

    public static function logUpdated(string $entityType, int $entityId, array $oldValues, array $newValues): self
    {
        $changedValues = array_diff_assoc($newValues, $oldValues);
        $changedOld = array_intersect_key($oldValues, $changedValues);

        return static::logAction('updated', $entityType, $entityId, $changedOld, $changedValues);
    }

    public static function logDeleted(string $entityType, int $entityId, array $oldValues): self
    {
        return static::logAction('deleted', $entityType, $entityId, $oldValues, null);
    }

    public static function logRestored(string $entityType, int $entityId, array $restoredValues): self
    {
        return static::logAction('restored', $entityType, $entityId, null, $restoredValues);
    }
}
