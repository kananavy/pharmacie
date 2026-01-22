<?php

namespace App\Observers;

use App\Models\Medicament;

class MedicamentObserver extends AuditObserver
{
    /**
     * Handle the Medicament "created" event.
     */
    public function created(Medicament $medicament): void
    {
        $this->logAudit('created', $medicament, null, $medicament->toArray());
    }

    /**
     * Handle the Medicament "updated" event.
     */
    public function updated(Medicament $medicament): void
    {
        $this->logAudit('updated', $medicament, $medicament->getOriginal(), $medicament->getChanges());
    }

    /**
     * Handle the Medicament "deleted" event.
     */
    public function deleted(Medicament $medicament): void
    {
        $this->logAudit('deleted', $medicament, $medicament->toArray(), null);
    }
}
