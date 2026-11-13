<?php

namespace Database\Seeders;

use App\Enums\Priorite;
use App\Enums\StatutSignalement;
use App\Models\User;
use App\Support\Telephone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $responsable = $this->utilisateur('Fatima Said', 'responsable@cleancomores.km', 'responsable', 'Moroni', 'ngazidja', '+269 333 12 34');

        $agents = [
            $this->utilisateur('Ibrahim Ali', 'ibrahim@cleancomores.km', 'agent', 'Moroni', 'ngazidja', '+269 344 55 66'),
            $this->utilisateur('Nassuf Mohamed', 'nassuf@cleancomores.km', 'agent', 'Moroni', 'ngazidja', '+269 344 55 67'),
            $this->utilisateur('Zaïnaba Halidi', 'zainaba@cleancomores.km', 'agent', 'Mutsamudu', 'ndzuani', '+269 344 55 68'),
            $this->utilisateur('Ahmed Bacar', 'ahmed@cleancomores.km', 'agent', 'Fomboni', 'mwali', '+269 344 55 69'),
        ];

        $citoyens = [
            $this->utilisateur('Mariama Ahmed', 'mariama@example.km', 'citoyen', 'Moroni', 'ngazidja', '+269 321 00 11'),
            $this->utilisateur('Youssouf Abdou', 'youssouf@example.km', 'citoyen', 'Mitsamiouli', 'ngazidja', '+269 322 45 78'),
            $this->utilisateur('Anissa Mohamed', 'anissa@example.km', 'citoyen', 'Mutsamudu', 'ndzuani'),
        ];

        // [catégorie, description, commune, zone, repère, lat, lng, statut visé, priorité, citoyen, agent, il y a N jours]
        $signalements = [
            ['depot_sauvage', 'Gros tas de déchets ménagers et de gravats derrière le marché Volo-Volo, l\'odeur est forte.', 'Moroni', 'ngazidja', 'Derrière le marché Volo-Volo, côté parking', -11.6985, 43.2551, 'a_verifier', null, 0, null, 0],
            ['poubelle_debordante', 'Le bac de la place de l\'Indépendance déborde depuis trois jours.', 'Moroni', 'ngazidja', 'Place de l\'Indépendance', -11.7022, 43.2560, 'en_cours', 'elevee', 0, 0, 1],
            ['plastique', 'Beaucoup de bouteilles plastiques sur la plage après la marée.', 'Mitsamiouli', 'ngazidja', 'Plage de Trou du Prophète', -11.3850, 43.3010, 'resolu', 'moyenne', 1, 0, 4],
            ['depot_sauvage', 'Dépôt de déchets verts et de ferraille au bord de la route nationale.', 'Mutsamudu', 'ndzuani', 'Sortie nord, RN2, près du pont', -12.1650, 44.3980, 'affecte', 'critique', 2, 2, 1],
            ['autre', 'Caniveau bouché par des déchets, l\'eau stagne devant l\'école.', 'Fomboni', 'mwali', 'Devant l\'école primaire', -12.2800, 43.7420, 'valide', 'elevee', 2, null, 2],
            ['poubelle_debordante', 'Points de collecte pleins dans tout le quartier.', 'Domoni', 'ndzuani', 'Quartier Hampanga', -12.2580, 44.5290, 'nouveau', null, 1, null, 0],
            ['plastique', 'Sachets plastiques accrochés dans la mangrove.', 'Moroni', 'ngazidja', 'Mangrove d\'Itsandra', -11.6670, 43.2600, 'verifie', 'faible', 0, 0, 9],
            ['depot_sauvage', 'Signalement en double du dépôt du marché.', 'Moroni', 'ngazidja', 'Marché Volo-Volo', -11.6990, 43.2548, 'rejete', null, 0, null, 6],
            ['depot_sauvage', 'Carcasses de voitures et déchets sur un terrain vague.', 'Foumbouni', 'ngazidja', 'Entrée sud de la ville', -11.8330, 43.4950, 'nouveau', null, 2, null, 0],
            ['poubelle_debordante', 'Bac renversé à côté de la mosquée.', 'Ouani', 'ndzuani', 'Près de la mosquée du vendredi', -12.1320, 44.4280, 'en_cours', 'moyenne', 1, 2, 3],
            ['plastique', 'Déchets plastiques le long du port.', 'Fomboni', 'mwali', 'Port de Fomboni', -12.2830, 43.7380, 'verifie', 'moyenne', 2, 3, 20],
            ['depot_sauvage', 'Ordures abandonnées près du stade.', 'Mbéni', 'ngazidja', 'Près du stade', -11.5050, 43.3870, 'verifie', 'elevee', 1, 1, 35],
            ['poubelle_debordante', 'Conteneur plein devant le marché central.', 'Mutsamudu', 'ndzuani', 'Marché central', -12.1680, 44.3990, 'verifie', 'moyenne', 0, 2, 60],
            ['autre', 'Dépôt de pneus usagés au bord de la corniche.', 'Iconi', 'ngazidja', 'Route de la corniche', -11.7300, 43.2450, 'verifie', 'faible', 1, 1, 100],
        ];

        $parcours = config('cleancomores.parcours');

        foreach ($signalements as [$cat, $desc, $commune, $zone, $repere, $lat, $lng, $cible, $prio, $cit, $ag, $jours]) {
            Carbon::setTestNow();
            $decalage = rand(2, 8);
            Carbon::setTestNow(now()->subDays($jours)->subHours($decalage));

            $s = $citoyens[$cit]->signalements()->create([
                'categorie' => $cat,
                'description' => $desc,
                'commune' => $commune,
                'zone' => $zone,
                'repere' => $repere,
                'latitude' => $lat,
                'longitude' => $lng,
            ]);

            $etapes = $cible === 'rejete'
                ? ['a_verifier', 'rejete']
                : array_slice($parcours, 1, array_search($cible, $parcours));

            $pasMinutes = intdiv(($jours * 24 + $decalage - 1) * 60, count($etapes) + 1);

            foreach ($etapes as $etape) {
                Carbon::setTestNow(now()->addMinutes($pasMinutes));
                $agent = $ag !== null ? $agents[$ag] : null;
                $par = in_array($etape, ['en_cours', 'resolu']) ? $agent : $responsable;
                $commentaire = null;

                switch ($etape) {
                    case 'valide':
                        $s->priorite = $prio;
                        $commentaire = 'Priorité définie : '.Priorite::from($prio)->getLabel().'.';
                        break;
                    case 'affecte':
                        $s->agent_id = $agent->id;
                        $s->consignes = 'Prévoir 2 personnes et des sacs renforcés. Photo avant/après.';
                        $commentaire = "Mission confiée à {$agent->name}.";
                        break;
                    case 'en_cours':
                        $commentaire = 'L\'équipe est sur place.';
                        break;
                    case 'resolu':
                        $s->compte_rendu = 'Zone entièrement nettoyée, 6 sacs collectés et évacués.';
                        $commentaire = $s->getRawOriginal('compte_rendu') ?? 'Zone nettoyée.';
                        break;
                    case 'verifie':
                        $commentaire = 'Résolution contrôlée et validée. Merci pour votre signalement !';
                        break;
                    case 'rejete':
                        $s->motif_rejet = 'Ce lieu fait déjà l\'objet d\'un signalement en cours de traitement.';
                        $commentaire = $s->motif_rejet;
                        break;
                }

                $s->changerStatut(StatutSignalement::from($etape), $par, $commentaire);
            }
        }

        Carbon::setTestNow();
    }

    private function utilisateur(string $nom, string $email, string $role, string $commune, string $zone, ?string $telephone = null): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $nom,
            'password' => 'password',
            'role' => $role,
            'commune' => $commune,
            'zone' => $zone,
            'telephone' => Telephone::normaliser($telephone),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
