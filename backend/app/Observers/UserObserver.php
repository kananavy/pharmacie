<?php

namespace App\Observers;

use App\Models\User;

class UserObserver extends AuditObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->logAudit('created', $user, null, $user->toArray());
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Don't log password changes explicitly in new_values/old_values
        $old = $user->getOriginal();
        $new = $user->getChanges();
        unset($old['password']);
        unset($new['password']);
        $this->logAudit('updated', $user, $old, $new);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->logAudit('deleted', $user, $user->toArray(), null);
    }
}
