{{-- Collectes à domicile effectuées par l'agent. Variable : $collectes (paginator) --}}
<x-layouts::espace role="agent" title="Mes collectes effectuées">
    <x-cc.entete-page titre="Mes collectes effectuées" :retour="route('agent.collectes.index')" retour-label="Collectes à domicile"
        :sous-titre="$collectes->total().' collecte(s) à domicile enregistrée(s).'" />

    @if ($collectes->isEmpty())
        <x-cc.vide titre="Aucune collecte effectuée" icone="clock">Les collectes que vous confirmez apparaîtront ici.</x-cc.vide>
    @else
        <ul class="space-y-3">
            @foreach ($collectes as $c)
                <li>
                    <a href="{{ route('agent.collectes.show', $c) }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs hover:border-lagon-600">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $c->abonnement->adresse }}, {{ $c->abonnement->commune }}</span>
                            <span class="block text-sm text-zinc-600">{{ $c->effectuee_at->translatedFormat('j F Y à H:i') }} · reçu <span class="font-mono">{{ $c->paiement->reference }}</span></span>
                        </span>
                        <x-cc.etat groupe="collectes" valeur="effectuee" taille="sm" />
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $collectes->links() }}</div>
    @endif
</x-layouts::espace>
