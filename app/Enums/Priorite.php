<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum Priorite: string implements HasLabel, HasColor, HasDescription
{
    case Faible = 'faible';
    case Moyenne = 'moyenne';
    case Elevee = 'elevee';
    case Critique = 'critique';

    public function getLabel(): string
    {
        return match ($this) {
            self::Faible => 'Faible',
            self::Moyenne => 'Moyenne',
            self::Elevee => 'Élevée',
            self::Critique => 'Critique',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Faible => 'info',
            self::Moyenne => 'success',
            self::Elevee => 'warning',
            self::Critique => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Faible => 'Collecte courante, sans risque immédiat.',
            self::Moyenne => 'Problème local nécessitant une intervention.',
            self::Elevee => 'Impact sanitaire ou accès perturbé.',
            self::Critique => 'Danger immédiat : prise en charge urgente.',
        };
    }
}