{{-- Historique des paiements du citoyen. Variable : $paiements (paginator, avec abonnement) --}}
<x-layouts::espace role="citoyen" title="Mes paiements">
    <x-cc.entete-page titre="Mes paiements" sous-titre="Tous vos paiements et reçus de collecte à domicile." :retour="route('citoyen.abonnements.index')" retour-label="Collecte à domicile" />

    @if ($paiements->isEmpty())
        <x-cc.vide titre="Aucun paiement" icone="receipt">Vos reçus apparaîtront ici après votre première souscription.</x-cc.vide>
    @else
        <ul class="space-y-3">
            @foreach ($paiements as $p)
                <li>
                    <a href="{{ route('citoyen.paiements.show', $p->reference) }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs hover:border-lagon-600">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-lagon-50 text-lagon-800"><x-cc.icon name="receipt" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-mono font-semibold">{{ $p->reference }}</span>
                            <span class="block text-sm text-zinc-600">{{ $p->abonnement->formule_nom }} · {{ $p->created_at->translatedFormat('j F Y') }}@if ($p->simulation) · simulation @endif</span>
                        </span>
                        <span class="text-right">
                            <span class="block font-semibold tabular-nums">{{ number_format($p->montant, 0, ',', ' ') }} KMF</span>
                            <x-cc.etat groupe="paiements" :valeur="$p->statut" taille="sm" />
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $paiements->links() }}</div>
    @endif
</x-layouts::espace>
