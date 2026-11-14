{{-- Historique des interventions de l'agent (statuts resolu / verifie). Variable : $missions --}}
@php $liste = collect($missions)->sortByDesc('updated_at'); @endphp
<x-layouts::espace role="agent" title="Historique">
    <x-cc.entete-page titre="Historique" sous-titre="Vos interventions terminées." />

    @if ($liste->isEmpty())
        <x-cc.vide titre="Pas encore d'intervention terminée" icone="clock">Vos missions transmises apparaîtront ici.</x-cc.vide>
    @else
        <ul class="space-y-3">
            @foreach ($liste as $m)
                <li class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="font-semibold"><x-cc.categorie :categorie="$m->categorie" /></p>
                            <p class="text-sm text-zinc-600"><span class="font-mono">{{ $m->reference }}</span> · {{ $m->commune }}</p>
                        </div>
                        <x-cc.statut :statut="$m->statut" taille="sm" />
                    </div>
                    @if ($m->compte_rendu)
                        <p class="mt-3 rounded-lg bg-sable-100 p-3 text-sm">{{ $m->compte_rendu->texte }}</p>
                        <p class="mt-2 text-xs text-zinc-600">Transmis le {{ $m->compte_rendu->created_at->translatedFormat('d M Y à H:i') }}</p>
                    @endif
                    <a href="{{ route('agent.missions.show', $m->id) }}" class="cc-touch -ms-2 mt-1 inline-flex items-center gap-1 rounded-lg px-2 text-sm font-medium text-lagon-800 hover:underline">Voir le détail<x-cc.icon name="chevron-right" class="size-4" /></a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts::espace>
