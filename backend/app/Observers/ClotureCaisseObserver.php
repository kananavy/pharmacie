<?php

namespace App\Observers;

use App\Models\ClotureCaisse;

class ClotureCaisseObserver extends AuditObserver
{
    /**
     * Handle the ClotureCaisse "created" event.
     */
    public function created(ClotureCaisse $clotureCaisse): void
    {
        $this->logAudit('created', $clotureCaisse, null, $clotureCaisse->toArray());
    }

    /**
     * Handle the ClotureCaisse "updated" event.
     */
    public function updated(ClotureCaisse $clotureCaisse): void
    {
        $this->logAudit('updated', $clotureCaisse, $clotureCaisse->getOriginal(), $clotureCaisse->getChanges());
    }

    /**
     * Handle the ClotureCaisse "deleted" event.
     */
    public function deleted(ClotureCaisse $clotureCaisse): void
    {
        $this->logAudit('deleted', $clotureCaisse, $clotureCaisse->toArray(), null);
    }
}
