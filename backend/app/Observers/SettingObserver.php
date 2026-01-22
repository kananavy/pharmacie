<?php

namespace App\Observers;

use App\Models\Setting;

class SettingObserver extends AuditObserver
{
    /**
     * Handle the Setting "created" event.
     */
    public function created(Setting $setting): void
    {
        $this->logAudit('created', $setting, null, $setting->toArray());
    }

    /**
     * Handle the Setting "updated" event.
     */
    public function updated(Setting $setting): void
    {
        $this->logAudit('updated', $setting, $setting->getOriginal(), $setting->getChanges());
    }

    /**
     * Handle the Setting "deleted" event.
     */
    public function deleted(Setting $setting): void
    {
        $this->logAudit('deleted', $setting, $setting->toArray(), null);
    }
}
