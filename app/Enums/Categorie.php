<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Categorie: string implements HasLabel
{
    case DepotSauvage = 'depot_sauvage';
    case PoubelleDebordante = 'poubelle_debordante';
    case DechetsPlastiques = 'dechets_plastiques';
    case DechetsDangereux = 'dechets_dangereux';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return match ($this) {
            self::DepotSauvage => 'Dépôt sauvage',
            self::PoubelleDebordante => 'Poubelle débordante',
            self::DechetsPlastiques => 'Déchets plastiques',
            self::DechetsDangereux => 'Déchets dangereux',
            self::Autre => 'Autre problème',
        };
    }
}