{{--
    Carte Leaflet (chargée à la demande par resources/js/app.js).
    :points = [['lat' => .., 'lng' => .., 'titre' => '..', 'statut' => 'en_cours', 'url' => '..'], ...]
    selection = true  → l'utilisateur peut cliquer pour placer un repère ; les champs
                        input[name=latitude] / input[name=longitude] du formulaire parent sont remplis.
    Le slot sert de repli (liste textuelle) si la carte ne peut pas se charger.
--}}
@props(['points' => [], 'selection' => false, 'hauteur' => 'h-72', 'label' => 'Carte des signalements'])
@php
    $points = collect($points)->map(fn ($p) => [
        'lat' => (float) data_get($p, 'lat'),
        'lng' => (float) data_get($p, 'lng'),
        'titre' => data_get($p, 'titre'),
        'statut' => data_get($p, 'statut'),
        'statutLabel' => config('cleancomores.statuts.'.data_get($p, 'statut').'.label'),
        'url' => data_get($p, 'url'),
    ])->values();
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
</div>
