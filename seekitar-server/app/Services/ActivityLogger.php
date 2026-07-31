<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(User $user, string $event, ?string $description = null, ?Model $loggable = null, ?array $old = null, ?array $new = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id'       => $user->id,
            'loggable_type' => $loggable ? $loggable->getMorphClass() : null,
            'loggable_id'   => $loggable ? $loggable->getKey() : null,
            'event'         => $event,
            'description'   => $description,
            'old_values'    => $old,
            'new_values'    => $new,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
        ]);
    }

    public function logSystem(string $event, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id'     => null,
            'event'       => $event,
            'description' => $description,
        ]);
    }
}
