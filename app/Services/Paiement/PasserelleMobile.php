<?php

namespace App\Services\Paiement;

use App\Models\Paiement;

/**
 * Passerelle de paiement mobile.
 *
 * Aujourd'hui : SimulationPasserelle (aucun argent réel, confirmation par un responsable).
 * Plus tard : une passerelle réelle (ex. Mvola, avec contrat marchand) dont initier()
 * envoie la demande à l'opérateur ; la confirmation arrive par un rappel signé
 * qui appelle Paiement::confirmer(null, $idTransaction). Un clic du citoyen
 * ne confirme JAMAIS un paiement.
 */
interface PasserelleMobile
{
    /** Code enregistré dans paiements.operateur. */
    public function code(): string;

    /** true = démonstration : le reçu porte la mention « simulation ». */
    public function estSimulation(): bool;

    /** Envoie la demande de paiement à l'opérateur. Le paiement reste « en attente ». */
    public function initier(Paiement $paiement): void;
}
