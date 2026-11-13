<?php

use App\Support\Telephone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remet les numéros déjà enregistrés au format +269XXXXXXX
 * (ex. "+269 321 00 11" → "+2693210011"), pour que la connexion par téléphone
 * et le contrôle des doublons fonctionnent. Un numéro non interprétable est laissé tel quel ;
 * un numéro qui créerait un doublon d'utilisateur n'est pas modifié.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'abonnements', 'paiements'] as $table) {
            DB::table($table)->whereNotNull('telephone')->orderBy('id')->each(function ($ligne) use ($table) {
                $normalise = Telephone::normaliser($ligne->telephone);
                if ($normalise === $ligne->telephone || ! Telephone::estValide($normalise)) {
                    return;
                }
                if ($table === 'users' && DB::table('users')->where('telephone', $normalise)->where('id', '!=', $ligne->id)->exists()) {
                    return;
                }
                DB::table($table)->where('id', $ligne->id)->update(['telephone' => $normalise]);
            });
        }
    }

    public function down(): void
    {
        // Rien à défaire : les anciennes formes n'étaient que des variantes d'écriture.
    }
};
