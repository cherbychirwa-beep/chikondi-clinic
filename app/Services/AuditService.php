<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Append an audit record. $actor defaults to the authenticated user (null for system/CLI events).
     */
    public function log(string $action, string $description, ?Model $subject = null, ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'description' => $description,
            'auditable_type' => $subject ? class_basename($subject) : null,
            'auditable_id' => $subject?->getKey(),
            'ip_address' => request()?->ip(),
        ]);
    }
}
