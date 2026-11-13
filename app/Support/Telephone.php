<?php

namespace App\Support;

/**
 * Numéros de téléphone comoriens (indicatif +269, numéro national à 7 chiffres).
 *  - mobiles : commencent par 3 ou 4 ; fixes : commencent par 7.
 * Forme enregistrée en base : +269XXXXXXX (ex. +2693210011).
 */
final class Telephone
{
    public const INDICATIF = '+269';

    /** Forme normalisée valide. */
    public const FORMAT = '/^\+269[347][0-9]{6}$/';

    /** Caractères acceptés à la saisie : chiffres, espaces, points, tirets, et + en tête. */
    public const SAISIE = '/^\+?[0-9 .\-]+$/';

    public const LONGUEUR_MIN = 7;    // 321 00 11

    public const LONGUEUR_MAX = 17;   // +269 321 00 11 (avec espaces) / 00269 3210011

    /**
     * "+269 321 00 11", "00269-321-00-11", "269 3210011", "321 00 11" → "+2693210011".
     * Une saisie impossible à interpréter est renvoyée telle quelle (la validation la refusera).
     */
    public static function normaliser(?string $saisie): ?string
    {
        $saisie = trim((string) $saisie);
        if ($saisie === '') {
            return null;
        }
        if (! preg_match(self::SAISIE, $saisie)) {
            return $saisie;
        }

        $chiffres = preg_replace('/\D/', '', $saisie);
        $international = str_starts_with($saisie, '+') || str_starts_with($chiffres, '00');
        $chiffres = preg_replace('/^00/', '', $chiffres);

        return match (true) {
            strlen($chiffres) === 7 && ! $international => self::INDICATIF.$chiffres,
            strlen($chiffres) === 10 && str_starts_with($chiffres, '269') => '+'.$chiffres,
            default => ($international ? '+' : '').$chiffres,
        };
    }

    public static function estValide(?string $saisie): bool
    {
        return (bool) preg_match(self::FORMAT, (string) self::normaliser($saisie));
    }

    /** "+2693210011" → "+269 321 00 11" (affichage). */
    public static function afficher(?string $numero): ?string
    {
        $n = self::normaliser($numero);

        return $n && preg_match(self::FORMAT, $n)
            ? sprintf('+269 %s %s %s', substr($n, 4, 3), substr($n, 7, 2), substr($n, 9, 2))
            : $numero;
    }
}
