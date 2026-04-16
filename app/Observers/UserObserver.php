<?php

namespace App\Observers;

use App\Models\User;
use App\Services\NotificationService;

class UserObserver
{
    public function created(User $user): void
    {
        if ($user->isDoctor()) {
            NotificationService::doctorStatusChanged([
                'id' => $user->id,
                'name' => $user->name,
                'is_available' => $user->doctorProfile?->is_available ?? true,
            ], $user->doctorProfile?->is_available ? 'available' : 'unavailable');
        }
    }

    public function updated(User $user): void
    {
        if ($user->isDoctor() && $user->wasChanged('is_active')) {
            NotificationService::doctorStatusChanged([
                'id' => $user->id,
                'name' => $user->name,
                'is_active' => $user->is_active,
            ], $user->is_active ? 'available' : 'unavailable');
        }
    }
}
