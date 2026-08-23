<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminLogService
{
    public function log(User $admin, string $action, ?Model $subject = null, ?string $description = null, ?string $ip = null): AdminLog
    {
        return AdminLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'ip_address' => $ip,
        ]);
    }
}
