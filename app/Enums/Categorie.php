<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Categorie: string implements HasLabel
{
    case DepotSauvage = 'depot_sauvage';
    case PoubelleDebordante = 'poubelle_debordante';
    case Plastique = 'plastique';
    case Autre = 'autre';

    public function getLabel(): string
    {
        return config("cleancomores.categories.{$this->value}.label");
    }
}