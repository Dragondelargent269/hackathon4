<?php

namespace App\Rules;

use App\Support\Telephone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Numéro comorien : 7 chiffres commençant par 3, 4 (mobile) ou 7 (fixe),
 * avec ou sans indicatif +269 / 00269. Espaces, points et tirets tolérés.
 * Normaliser ensuite la valeur avec Telephone::normaliser() avant enregistrement.
 */
class TelephoneComores implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $saisie = trim((string) $value);

        if (! preg_match(Telephone::SAISIE, $saisie)) {
            $fail('Le numéro de téléphone ne doit contenir que des chiffres (et éventuellement + au début).');

            return;
        }

        if (mb_strlen($saisie) < Telephone::LONGUEUR_MIN || mb_strlen($saisie) > Telephone::LONGUEUR_MAX) {
            $fail('Le numéro de téléphone doit comporter 7 chiffres, ou 10 avec l\'indicatif +269.');

            return;
        }

        if (! Telephone::estValide($saisie)) {
            $fail('Ce numéro n\'est pas un numéro comorien valide : 7 chiffres commençant par 3, 4 ou 7 (ex. : 321 00 11 ou +269 321 00 11).');
        }
    }
}
