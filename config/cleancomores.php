<?php

/*
|--------------------------------------------------------------------------
| CleanComores — référentiel partagé front / back
|--------------------------------------------------------------------------
|
| Les vues lisent les libellés, couleurs et règles d'affichage ici.
| Le back-end doit utiliser les MÊMES clés (statut, priorité, catégorie)
| en base / dans ses enums.
|
*/

return [

    // Cycle de vie : nouveau → a_verifier → valide → affecte → en_cours → resolu → verifie | rejete
    'statuts' => [
        'nouveau' => [
            'label' => 'Nouveau',
            'description' => 'Votre signalement a bien été reçu.',
            'classes' => 'bg-slate-100 text-slate-800 ring-slate-300',
            'point' => 'bg-slate-500',
            'icone' => 'inbox',
        ],
        'a_verifier' => [
            'label' => 'À vérifier',
            'description' => 'Un responsable examine votre signalement.',
            'classes' => 'bg-amber-50 text-amber-900 ring-amber-300',
            'point' => 'bg-amber-500',
            'icone' => 'search',
        ],
        'valide' => [
            'label' => 'Validé',
            'description' => 'Le problème est confirmé et sera traité.',
            'classes' => 'bg-sky-50 text-sky-900 ring-sky-300',
            'point' => 'bg-sky-600',
            'icone' => 'check',
        ],
        'affecte' => [
            'label' => 'Affecté',
            'description' => 'Une équipe de terrain a été désignée.',
            'classes' => 'bg-violet-50 text-violet-900 ring-violet-300',
            'point' => 'bg-violet-600',
            'icone' => 'user',
        ],
        'en_cours' => [
            'label' => 'En cours',
            'description' => 'L\'équipe intervient sur place.',
            'classes' => 'bg-orange-50 text-orange-900 ring-orange-300',
            'point' => 'bg-orange-500',
            'icone' => 'truck',
        ],
        'resolu' => [
            'label' => 'Résolu',
            'description' => 'L\'intervention est terminée, en attente de validation.',
            'classes' => 'bg-teal-50 text-teal-900 ring-teal-300',
            'point' => 'bg-teal-600',
            'icone' => 'check-circle',
        ],
        'verifie' => [
            'label' => 'Vérifié',
            'description' => 'La résolution a été contrôlée et validée. Merci !',
            'classes' => 'bg-emerald-100 text-emerald-900 ring-emerald-400',
            'point' => 'bg-emerald-700',
            'icone' => 'shield',
        ],
        'rejete' => [
            'label' => 'Rejeté',
            'description' => 'Le signalement n\'a pas été retenu (voir le motif).',
            'classes' => 'bg-red-50 text-red-900 ring-red-300',
            'point' => 'bg-red-600',
            'icone' => 'x-circle',
        ],
    ],

    // Ordre de progression affiché au citoyen (rejete est hors parcours)
    'parcours' => ['nouveau', 'a_verifier', 'valide', 'affecte', 'en_cours', 'resolu', 'verifie'],

    'priorites' => [
        'faible' => ['label' => 'Faible', 'classes' => 'bg-zinc-100 text-zinc-800 ring-zinc-300', 'barres' => 1],
        'moyenne' => ['label' => 'Moyenne', 'classes' => 'bg-yellow-50 text-yellow-900 ring-yellow-300', 'barres' => 2],
        'elevee' => ['label' => 'Élevée', 'classes' => 'bg-orange-100 text-orange-900 ring-orange-400', 'barres' => 3],
        'critique' => ['label' => 'Critique', 'classes' => 'bg-red-600 text-white ring-red-700', 'barres' => 4],
    ],

    'categories' => [
        'depot_sauvage' => ['label' => 'Dépôt sauvage', 'icone' => 'trash', 'aide' => 'Tas de déchets abandonnés dans la rue, un terrain, une plage…'],
        'poubelle_debordante' => ['label' => 'Poubelle débordante', 'icone' => 'bin', 'aide' => 'Bac ou point de collecte plein qui déborde.'],
        'plastique' => ['label' => 'Déchets plastiques', 'icone' => 'bottle', 'aide' => 'Bouteilles, sachets, plastiques sur le littoral ou en ville.'],
        'autre' => ['label' => 'Autre', 'icone' => 'dots', 'aide' => 'Tout autre problème de propreté.'],
    ],

    // Zones = îles ; communes rattachées (liste indicative, à aligner avec la base)
    'zones' => [
        'ngazidja' => ['label' => 'Ngazidja (Grande Comore)', 'communes' => ['Moroni', 'Mitsamiouli', 'Mbéni', 'Foumbouni', 'Iconi', 'Mitsoudjé']],
        'ndzuani' => ['label' => 'Ndzuani (Anjouan)', 'communes' => ['Mutsamudu', 'Domoni', 'Ouani', 'Sima', 'Moya']],
        'mwali' => ['label' => 'Mwali (Mohéli)', 'communes' => ['Fomboni', 'Nioumachoua', 'Wanani']],
    ],

    // Centre de carte par défaut (archipel)
    'carte' => ['lat' => -11.95, 'lng' => 43.75, 'zoom' => 8],
];
