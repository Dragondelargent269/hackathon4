<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatutSignalement: string implements HasLabel, HasColor
{
    case Nouveau = 'nouveau';
    case AVerifier = 'a_verifier';
    case Valide = 'valide';
    case Affecte = 'affecte';
    case EnCours = 'en_cours';
    case Resolu = 'resolu';
    case Verifie = 'verifie';
    case Rejete = 'rejete';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::AVerifier => 'À vérifier',
            self::Valide => 'Validé',
            self::Affecte => 'Affecté',
            self::EnCours => 'En cours',
            self::Resolu => 'Résolu',
            self::Verifie => 'Vérifié',
            self::Rejete => 'Rejeté',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Nouveau => 'gray',
            self::AVerifier => 'warning',
            self::Valide, self::Affecte => 'info',
            self::EnCours => 'primary',
            self::Resolu, self::Verifie => 'success',
            self::Rejete => 'danger',
        };
    }

    /**
     * Statuts vers lesquels on a le droit de passer depuis le statut actuel.
     *
     * @return array<self>
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::Nouveau => [self::AVerifier, self::Valide, self::Rejete],
            self::AVerifier => [self::Valide, self::Rejete],
            self::Valide => [self::Affecte],
                        self::Affecte => [self::EnCours, self::Affecte],
            self::EnCours => [self::Resolu],
            self::Resolu => [self::Verifie, self::EnCours],
            self::Verifie, self::Rejete => [],
        };
    }

    public function peutPasserA(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }
}