<?php

namespace App\Policies;

use App\Models\GmuNotification;
use App\Models\User;
use App\Policies\Concerns\ChecksGmuRoles;

/**
 * Notifications belong to one person. Reading or dismissing someone else's is
 * never legitimate (finding M4).
 */
class GmuNotificationPolicy
{
    use ChecksGmuRoles;

    public function view(User $user, GmuNotification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function update(User $user, GmuNotification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
