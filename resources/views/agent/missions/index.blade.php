{{-- Missions affectées à l'agent connecté (statuts affecte / en_cours). Variable : $missions --}}
@php
    $liste = collect($missions);
    $aDemarrer = $liste->where('statut', 'affecte');
    $enCours = $liste->where('statut', 'en_cours');
@endphp
<x-layouts::espace role="agent" title="Mes missions">
    <x-cc.entete-page titre="Mes missions" :sous-titre="$liste->count().' mission(s) en attente ou en cours.'" />

    @if ($liste->isEmpty())
        <x-cc.vide titre="Aucune mission pour le moment" icone="truck">Les nouvelles missions apparaîtront ici dès qu'un responsable vous les affectera.</x-cc.vide>
    @else
        @foreach ([['En cours', $enCours], ['À démarrer', $aDemarrer]] as [$titre, $groupe])
            @if ($groupe->isNotEmpty())
                <h2 class="mb-3 mt-6 flex items-center gap-2 text-lg font-semibold first:mt-0">{{ $titre }} <span class="rounded-full bg-zinc-200 px-2 text-sm tabular-nums">{{ $groupe->count() }}</span></h2>
                <ul class="space-y-3">
                    @foreach ($groupe->sortBy(fn ($m) => -(config('cleancomores.priorites.'.$m->priorite.'.barres') ?? 0)) as $m)
                        <x-cc.signalement-ligne :signalement="$m" :href="route('agent.missions.show', $m->id)" avec-priorite />
                    @endforeach
                </ul>
            @endif
        @endforeach
    @endif
</x-layouts::espace>
