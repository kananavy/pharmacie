<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

abstract class AuditObserver
{
    /**
     * Get the user ID or null if not authenticated.
     */
    protected function getUserId(): ?int
    {
        return Auth::check() ? Auth::id() : null;
    }

    /**
     * Get the IP address and user agent.
     */
    protected function getRequestInfo(): array
    {
        return [
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'url' => request()->fullUrl(),
        ];
    }

    /**
     * Log a model event.
     */
    protected function logAudit(string $event, Model $model, ?array $oldValues = null, ?array $newValues = null): void
    {
        // Filter out common Laravel timestamps from audit
        $excludeAttributes = ['created_at', 'updated_at', 'remember_token'];

        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($excludeAttributes));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($excludeAttributes));
        }
        
        // Don't log if no actual changes and it's an 'updated' event
        if ($event === 'updated' && empty(array_diff_assoc($newValues, $oldValues))) {
            return;
        }

        AuditLog::create(array_merge([
            'user_id' => $this->getUserId(),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ], $this->getRequestInfo()));
    }
}
