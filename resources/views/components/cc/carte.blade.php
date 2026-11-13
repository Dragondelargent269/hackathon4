{{--
    Carte Leaflet (chargée à la demande par resources/js/app.js).
    :points = [['lat' => .., 'lng' => .., 'titre' => '..', 'statut' => 'en_cours', 'url' => '..'], ...]
        type    = signalement (défaut, rond coloré selon le statut) | point_collecte | collecte_domicile (carrés)
        details = ligne de texte facultative dans la bulle ; lien = libellé du lien (défaut « Ouvrir le dossier »)
    Couleurs : config('cleancomores.marqueurs') et config('cleancomores.types_points').
    selection = true  → l'utilisateur peut cliquer pour placer un repère ; les champs
                        input[name=latitude] / input[name=longitude] du formulaire parent sont remplis.
    legende   = true  → légende sous la carte (statuts + types de points présents).
    Le slot sert de repli (liste textuelle) si la carte ne peut pas se charger.
--}}
@props(['points' => [], 'selection' => false, 'hauteur' => 'h-72', 'label' => 'Carte des signalements', 'legende' => false])
@php
    $groupes = config('cleancomores.marqueurs');
    $types = config('cleancomores.types_points');
    $groupeDe = function (?string $statut) use ($groupes) {
        foreach ($groupes as $cle => $g) {
            if (in_array($statut, $g['statuts'], true)) {
                return $cle;
            }
        }

        return 'non_traite';
    };

    $points = collect($points)->map(function ($p) use ($groupes, $types, $groupeDe) {
        $statut = data_get($p, 'statut');
        $statut = $statut instanceof \BackedEnum ? $statut->value : $statut;
        $type = data_get($p, 'type', 'signalement');

        if ($type === 'signalement') {
            $groupe = $groupes[$groupeDe($statut)];
            $couleur = $groupe['couleur'];
            $clignote = $groupe['clignote'];
            $statutLabel = config('cleancomores.statuts.'.$statut.'.label');
            $typeLabel = null;
        } else {
            $couleur = $types[$type]['couleur'] ?? '#0a7661';
            $clignote = false;
            $statutLabel = data_get($p, 'statutLabel');
            $typeLabel = $types[$type]['label'] ?? null;
        }

        return [
            'lat' => (float) data_get($p, 'lat'),
            'lng' => (float) data_get($p, 'lng'),
            'titre' => data_get($p, 'titre'),
            'type' => $type,
            'typeLabel' => $typeLabel,
            'statut' => $statut,
            'statutLabel' => $statutLabel,
            'couleur' => $couleur,
            'clignote' => $clignote,
            'details' => data_get($p, 'details'),
            'url' => data_get($p, 'url'),
            'lien' => data_get($p, 'lien'),
        ];
    })->values();

    $typesPresents = $points->pluck('type')->unique()->reject(fn ($t) => $t === 'signalement');
@endphp
<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-xl border border-zinc-200 bg-lagon-50']) }}>
    <div
        data-carte
        data-points='@json($points)'
        data-centre='@json(config('cleancomores.carte'))'
        @if ($selection) data-selection @endif
        role="region"
        aria-label="{{ $label }}"
        class="{{ $hauteur }} w-full"
    >
        <div class="grid h-full place-items-center p-4 text-center text-sm text-zinc-600" data-carte-repli>
            <div>
                <x-cc.icon name="map-pin" class="mx-auto size-6 text-lagon-700" />
                <p class="mt-1">Chargement de la carte…</p>
                {{ $slot }}
            </div>
        </div>
    </div>
    @if ($legende)
        <ul class="flex flex-wrap gap-x-4 gap-y-1.5 border-t border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-700" aria-label="Légende de la carte">
            @foreach ($points->contains('type', 'signalement') || $points->isEmpty() ? $groupes : [] as $g)
                <li class="flex items-center gap-1.5">
                    <span @class(['inline-block size-3 rounded-full ring-2 ring-white', 'animate-pulse' => $g['clignote']]) style="background: {{ $g['couleur'] }}" aria-hidden="true"></span>{{ $g['label'] }}
                </li>
            @endforeach
            @foreach ($typesPresents as $t)
                <li class="flex items-center gap-1.5">
                    <span class="inline-block size-3 rounded-sm" style="background: {{ $types[$t]['couleur'] }}" aria-hidden="true"></span>{{ $types[$t]['label'] }}
                </li>
            @endforeach
        </ul>
    @endif
</div>
