<?php

namespace App\Observers;

use App\Models\Vente;

class VenteObserver extends AuditObserver
{
    /**
     * Handle the Vente "created" event.
     */
    public function created(Vente $vente): void
    {
        $this->logAudit('created', $vente, null, $vente->toArray());
    }

    /**
     * Handle the Vente "updated" event.
     */
    public function updated(Vente $vente): void
    {
        $this->logAudit('updated', $vente, $vente->getOriginal(), $vente->getChanges());
    }

    /**
     * Handle the Vente "deleted" event.
     */
    public function deleted(Vente $vente): void
    {
        $this->logAudit('deleted', $vente, $vente->toArray(), null);
    }
}
