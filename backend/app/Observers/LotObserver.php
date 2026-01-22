<?php

namespace App\Observers;

use App\Models\Lot;

class LotObserver extends AuditObserver
{
    /**
     * Handle the Lot "created" event.
     */
    public function created(Lot $lot): void
    {
        $this->logAudit('created', $lot, null, $lot->toArray());
    }

    /**
     * Handle the Lot "updated" event.
     */
    public function updated(Lot $lot): void
    {
        $this->logAudit('updated', $lot, $lot->getOriginal(), $lot->getChanges());
    }

    /**
     * Handle the Lot "deleted" event.
     */
    public function deleted(Lot $lot): void
    {
        $this->logAudit('deleted', $lot, $lot->toArray(), null);
    }
}
