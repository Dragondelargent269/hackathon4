<?php

namespace App\Services\Paiement;

use App\Models\Paiement;

/**
 * Mode démonstration : aucune transaction réelle.
 * Le paiement reste « en attente » jusqu'à ce qu'un responsable le confirme
 * (ou le refuse) dans son espace ; cette action est enregistrée.
 */
class SimulationPasserelle implements PasserelleMobile
{
    public function code(): string
    {
        return 'simulation';
    }

    public function estSimulation(): bool
    {
        return true;
    }

    public function initier(Paiement $paiement): void
    {
        // Rien n'est envoyé à un opérateur.
    }
}
