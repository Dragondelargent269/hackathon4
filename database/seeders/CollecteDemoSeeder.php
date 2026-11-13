<?php

namespace Database\Seeders;

use App\Models\Abonnement;
use App\Models\Collecte;
use App\Models\Formule;
use App\Models\Paiement;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Formules (tarifs d'exemple, modifiables dans /admin), points de collecte
 * et quelques abonnements de démonstration. Relançable sans doublon.
 */
class CollecteDemoSeeder extends Seeder
{
    public function run(): void
    {
        $formules = [
            ['slug' => 'ponctuelle', 'nom' => 'Ponctuelle', 'prix' => 2500, 'duree_jours' => 30, 'collectes_incluses' => 1, 'ordre' => 1,
                'description' => 'Un passage unique, idéal après un événement ou un grand ménage.',
                'services' => ['1 collecte à domicile', 'Jusqu\'à 4 sacs', 'Reçu numérique avec QR code']],
            ['slug' => 'mensuelle', 'nom' => 'Mensuelle', 'prix' => 7500, 'duree_jours' => 30, 'collectes_incluses' => 4, 'ordre' => 2,
                'description' => 'Un passage par semaine pendant un mois.',
                'services' => ['4 collectes à domicile', 'Jusqu\'à 4 sacs par passage', 'Choix du créneau']],
            ['slug' => 'trimestrielle', 'nom' => 'Trimestrielle', 'prix' => 20000, 'duree_jours' => 90, 'collectes_incluses' => 13, 'ordre' => 3,
                'description' => 'Un passage par semaine pendant trois mois, au meilleur prix.',
                'services' => ['13 collectes à domicile', 'Jusqu\'à 4 sacs par passage', 'Choix du créneau', 'Prix réduit']],
        ];
        foreach ($formules as $f) {
            Formule::updateOrCreate(['slug' => $f['slug']], $f + ['active' => true]);
        }

        $points = [
            ['nom' => 'Centre de tri de Moroni', 'commune' => 'Moroni', 'zone' => 'ngazidja', 'latitude' => -11.7045, 'longitude' => 43.2573, 'horaires' => 'Lun–Sam 7 h–17 h', 'dechets_acceptes' => 'Tous déchets ménagers, plastiques'],
            ['nom' => 'Point de dépôt de Mitsamiouli', 'commune' => 'Mitsamiouli', 'zone' => 'ngazidja', 'latitude' => -11.3847, 'longitude' => 43.2862, 'horaires' => 'Lun–Ven 7 h–15 h', 'dechets_acceptes' => 'Déchets ménagers'],
            ['nom' => 'Point de dépôt de Foumbouni', 'commune' => 'Foumbouni', 'zone' => 'ngazidja', 'latitude' => -11.8599, 'longitude' => 43.4950, 'horaires' => 'Mar, Jeu, Sam 7 h–12 h', 'dechets_acceptes' => 'Déchets ménagers, verre'],
            ['nom' => 'Centre de collecte de Mutsamudu', 'commune' => 'Mutsamudu', 'zone' => 'ndzuani', 'latitude' => -12.1660, 'longitude' => 44.3970, 'horaires' => 'Lun–Sam 7 h–17 h', 'dechets_acceptes' => 'Tous déchets ménagers, plastiques'],
            ['nom' => 'Point de dépôt de Domoni', 'commune' => 'Domoni', 'zone' => 'ndzuani', 'latitude' => -12.2560, 'longitude' => 44.5300, 'horaires' => 'Lun–Ven 7 h–15 h', 'dechets_acceptes' => 'Déchets ménagers'],
            ['nom' => 'Centre de collecte de Fomboni', 'commune' => 'Fomboni', 'zone' => 'mwali', 'latitude' => -12.2800, 'longitude' => 43.7420, 'horaires' => 'Lun–Sam 7 h–16 h', 'dechets_acceptes' => 'Tous déchets ménagers'],
        ];
        foreach ($points as $p) {
            PointCollecte::updateOrCreate(['nom' => $p['nom']], $p + ['actif' => true]);
        }

        $responsable = User::where('role', 'responsable')->first();
        $mariama = User::where('email', 'mariama@example.km')->first();
        $youssouf = User::where('email', 'youssouf@example.km')->first();

        // Mariama : abonnement mensuel payé (simulation confirmée) + une demande de passage
        if ($mariama && $responsable && ! $mariama->abonnements()->exists()) {
            $abonnement = Abonnement::souscrire($mariama, Formule::where('slug', 'mensuelle')->first(), [
                'adresse' => 'Quartier Magoudjou, maison n° 12', 'commune' => 'Moroni', 'zone' => 'ngazidja',
                'repere' => 'Portail bleu, face à la boulangerie', 'latitude' => -11.6980, 'longitude' => 43.2560,
                'telephone' => '+2693210011',
            ]);
            $paiement = Paiement::ouvrir($abonnement, 'simulation', '+2693210011', true);
            $paiement->confirmer($responsable);
            Collecte::demander($abonnement->fresh(), 'matin', 'Trois sacs devant le portail.');
        }

        // Youssouf : paiement en attente de confirmation par le responsable
        if ($youssouf && ! $youssouf->abonnements()->exists()) {
            $abonnement = Abonnement::souscrire($youssouf, Formule::where('slug', 'ponctuelle')->first(), [
                'adresse' => 'Route de la plage, maison Abdou', 'commune' => 'Mitsamiouli', 'zone' => 'ngazidja',
                'latitude' => -11.3870, 'longitude' => 43.2900, 'telephone' => '+2693224578',
            ]);
            Paiement::ouvrir($abonnement, 'simulation', '+2693224578', true);
        }
    }
}
