{{-- Barre de progression du parcours (vue citoyen). Le statut « rejete » est affiché à part. --}}
@props(['statut'])
@php
    $cle = $statut instanceof \BackedEnum ? $statut->value : $statut;
    $parcours = config('cleancomores.parcours');
    $index = array_search($cle, $parcours, true);
@endphp
@if ($cle === 'rejete')
    <x-cc.alerte type="erreur" titre="Signalement non retenu">Consultez le motif ci-dessous.</x-cc.alerte>
@else
    <div {{ $attributes }}>
        <p class="sr-only">Étape {{ $index + 1 }} sur {{ count($parcours) }} : {{ config("cleancomores.statuts.$cle.label") }}</p>
        <ol class="grid grid-cols-7 gap-1" aria-hidden="true">
            @foreach ($parcours as $i => $etape)
                <li class="h-2 rounded-full {{ $i <= $index ? 'bg-lagon-600' : 'bg-zinc-200' }}"></li>
            @endforeach
        </ol>
        <div class="mt-2 flex justify-between text-xs text-zinc-600" aria-hidden="true">
            <span>Reçu</span><span>En traitement</span><span>Vérifié</span>
        </div>
    </div>
@endif
