<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Données FICTIVES pour prévisualiser le front-end (routes/maquette.php).
 * Elles reproduisent la forme des objets attendus par les vues
 * (voir FRONTEND.md). À supprimer une fois le back-end branché.
 */
class Maquette
{
    public static function utilisateur(string $role): object
    {
        return match ($role) {
            'responsable' => (object) ['id' => 2, 'name' => 'Fatima Said', 'email' => 'f.said@mairie-moroni.km', 'telephone' => '+269 333 12 34', 'commune' => 'Moroni', 'role' => 'responsable'],
            'agent' => (object) ['id' => 11, 'name' => 'Ibrahim Ali', 'email' => null, 'telephone' => '+269 344 55 66', 'commune' => 'Moroni', 'role' => 'agent'],
            default => (object) ['id' => 1, 'name' => 'Mariama Ahmed', 'email' => 'mariama@example.km', 'telephone' => '+269 321 00 11', 'commune' => 'Moroni', 'role' => 'citoyen', 'notif_email' => true, 'notif_changement_statut' => true, 'notif_resolution' => true],
        };
    }

    public static function agents(): Collection
    {
        return collect([
            (object) ['id' => 11, 'name' => 'Ibrahim Ali', 'zone' => 'ngazidja', 'missions_en_cours' => 2, 'disponible' => true],
            (object) ['id' => 12, 'name' => 'Nassuf Mohamed', 'zone' => 'ngazidja', 'missions_en_cours' => 0, 'disponible' => true],
            (object) ['id' => 13, 'name' => 'Zaïnaba Halidi', 'zone' => 'ndzuani', 'missions_en_cours' => 1, 'disponible' => true],
            (object) ['id' => 14, 'name' => 'Ahmed Bacar', 'zone' => 'mwali', 'missions_en_cours' => 4, 'disponible' => false],
        ]);
    }

    public static function signalements(): Collection
    {
        $now = Carbon::now();
        $citoyens = [
            (object) ['name' => 'Mariama Ahmed', 'telephone' => '+269 321 00 11', 'email' => 'mariama@example.km'],
            (object) ['name' => 'Youssouf Abdou', 'telephone' => '+269 322 45 78', 'email' => null],
            (object) ['name' => 'Anissa Mohamed', 'telephone' => null, 'email' => 'anissa.m@example.km'],
        ];
        $agents = self::agents()->keyBy('id');

        $brut = [
            [142, 'depot_sauvage', 'Gros tas de déchets ménagers et de gravats derrière le marché Volo-Volo, l\'odeur est forte et les chiens éparpillent tout.', 'Moroni', 'ngazidja', 'Derrière le marché Volo-Volo, côté parking', -11.6985, 43.2551, 'a_verifier', null, 0, null, 0],
            [141, 'poubelle_debordante', 'Le bac de la place de l\'Indépendance déborde depuis 3 jours.', 'Moroni', 'ngazidja', 'Place de l\'Indépendance', -11.7022, 43.2560, 'en_cours', 'elevee', 0, 11, 1],
            [140, 'plastique', 'Beaucoup de bouteilles plastiques sur la plage après la marée.', 'Mitsamiouli', 'ngazidja', 'Plage de Trou du Prophète', -11.3850, 43.3010, 'resolu', 'moyenne', 1, 11, 4],
            [139, 'depot_sauvage', 'Dépôt de déchets verts et de ferraille au bord de la route nationale.', 'Mutsamudu', 'ndzuani', 'Sortie nord, RN2, près du pont', -12.1650, 44.3980, 'affecte', 'critique', 2, 13, 1],
            [138, 'autre', 'Caniveau bouché par des déchets, l\'eau stagne devant l\'école.', 'Fomboni', 'mwali', 'Devant l\'école primaire', -12.2800, 43.7420, 'valide', 'elevee', 2, null, 2],
            [137, 'poubelle_debordante', 'Points de collecte pleins dans tout le quartier.', 'Domoni', 'ndzuani', 'Quartier Hampanga', -12.2580, 44.5290, 'nouveau', null, 1, null, 0],
            [136, 'plastique', 'Sachets plastiques accrochés dans la mangrove.', 'Moroni', 'ngazidja', 'Mangrove d\'Itsandra', -11.6670, 43.2600, 'verifie', 'faible', 0, 11, 9],
            [135, 'depot_sauvage', 'Signalement en double du dépôt du marché.', 'Moroni', 'ngazidja', 'Marché Volo-Volo', -11.6990, 43.2548, 'rejete', null, 0, null, 6],
            [134, 'depot_sauvage', 'Carcasses de voitures et déchets sur un terrain vague.', 'Foumbouni', 'ngazidja', 'Entrée sud de la ville', -11.8330, 43.4950, 'nouveau', null, 2, null, 0],
            [133, 'poubelle_debordante', 'Bac renversé à côté de la mosquée.', 'Ouani', 'ndzuani', 'Près de la mosquée du vendredi', -12.1320, 44.4280, 'en_cours', 'moyenne', 1, 13, 3],
        ];

        $ordre = config('cleancomores.parcours');

        return collect($brut)->map(function ($r) use ($now, $citoyens, $agents, $ordre) {
            [$num, $cat, $desc, $commune, $zone, $repere, $lat, $lng, $statut, $prio, $cit, $agentId, $joursDepuis] = $r;
            $cree = $now->copy()->subDays($joursDepuis)->subHours($num % 9 + 2);

            // Historique cohérent avec le statut
            $etapes = $statut === 'rejete' ? ['nouveau', 'a_verifier', 'rejete'] : array_slice($ordre, 0, array_search($statut, $ordre) + 1);
            $commentaires = [
                'a_verifier' => null,
                'valide' => $prio ? 'Priorité définie : '.config("cleancomores.priorites.$prio.label").'.' : null,
                'affecte' => 'Mission confiée à l\'équipe de terrain.',
                'en_cours' => 'L\'équipe est sur place.',
                'resolu' => 'Zone nettoyée, 6 sacs collectés.',
                'verifie' => 'Résolution contrôlée sur photo. Merci pour votre signalement !',
                'rejete' => 'Ce lieu fait déjà l\'objet du signalement CC-2026-0142, qui est en cours de traitement.',
            ];
            $auteurs = ['nouveau' => 'Citoyen', 'a_verifier' => 'Fatima Said', 'valide' => 'Fatima Said', 'affecte' => 'Fatima Said', 'en_cours' => $agentId ? $agents[$agentId]->name : null, 'resolu' => $agentId ? $agents[$agentId]->name : null, 'verifie' => 'Fatima Said', 'rejete' => 'Fatima Said'];
            $n = count($etapes);
            $historique = collect($etapes)->values()->map(fn ($e, $i) => (object) [
                'statut' => $e,
                'created_at' => $cree->copy()->addHours((int) round($i * max(1, $joursDepuis * 24) / max(1, $n))),
                'auteur' => $auteurs[$e] ?? null,
                'commentaire' => $commentaires[$e] ?? null,
            ]);

            return (object) [
                'id' => $num,
                'reference' => sprintf('CC-2026-%04d', $num),
                'categorie' => $cat,
                'description' => $desc,
                'commune' => $commune,
                'zone' => $zone,
                'repere' => $repere,
                'latitude' => $lat,
                'longitude' => $lng,
                'photo_url' => null,
                'statut' => $statut,
                'priorite' => $prio,
                'citoyen' => $citoyens[$cit],
                'agent' => $agentId ? $agents[$agentId] : null,
                'consignes' => $agentId ? 'Prévoir 2 personnes et des sacs renforcés. Prendre une photo avant/après.' : null,
                'motif_rejet' => $statut === 'rejete' ? $commentaires['rejete'] : null,
                'compte_rendu' => in_array($statut, ['resolu', 'verifie']) ? (object) ['texte' => 'Zone entièrement nettoyée. 6 sacs de plastiques collectés et évacués vers la déchetterie.', 'photo_url' => null, 'created_at' => $historique->last()->created_at] : null,
                'historique' => $historique,
                'created_at' => $cree,
                'updated_at' => $historique->last()->created_at,
            ];
        });
    }

    public static function signalement(int $id): object
    {
        return self::signalements()->firstWhere('id', $id) ?? abort(404);
    }

    public static function statistiques(): array
    {
        return [
            'mois' => [
                ['mois' => 'Juin', 'recus' => 38, 'resolus' => 25],
                ['mois' => 'Juil.', 'recus' => 52, 'resolus' => 34],
                ['mois' => 'Août', 'recus' => 61, 'resolus' => 47],
                ['mois' => 'Sept.', 'recus' => 49, 'resolus' => 45],
                ['mois' => 'Oct.', 'recus' => 70, 'resolus' => 58],
                ['mois' => 'Nov.', 'recus' => 34, 'resolus' => 21],
            ],
            'zones' => [
                ['zone' => 'ngazidja', 'total' => 168, 'resolus' => 131],
                ['zone' => 'ndzuani', 'total' => 104, 'resolus' => 71],
                ['zone' => 'mwali', 'total' => 32, 'resolus' => 28],
            ],
            'categories' => [
                ['categorie' => 'depot_sauvage', 'total' => 121],
                ['categorie' => 'poubelle_debordante', 'total' => 97],
                ['categorie' => 'plastique', 'total' => 64],
                ['categorie' => 'autre', 'total' => 22],
            ],
            'delai_moyen_jours' => 3.4,
        ];
    }
}
