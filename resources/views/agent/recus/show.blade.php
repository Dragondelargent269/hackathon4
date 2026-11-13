{{-- Résultat de vérification d'un reçu. Variables : $reference, $paiement (ou null), $qrInvalide, $viaQr --}}
@php
    $p = $paiement;
    $a = $p?->abonnement;
    $valable = $p && $p->statut === 'confirme' && $a->estUtilisable();
@endphp
<x-layouts::espace role="agent" title="Reçu {{ $reference }}">
    <x-cc.entete-page titre="Vérification du reçu" :sous-titre="$reference" :retour="route('agent.recus.index')" retour-label="Vérifier un autre reçu" />

    @if ($qrInvalide)
        <x-cc.alerte type="erreur" titre="QR code invalide">Le code secret de ce QR code ne correspond pas au reçu {{ $reference }}. Ce reçu n'est <strong>pas authentique</strong> : ne faites pas la collecte.</x-cc.alerte>
    @elseif (! $p)
        <x-cc.alerte type="erreur" titre="Reçu introuvable">Aucun paiement ne porte la référence {{ $reference }}. Vérifiez la saisie ; si elle est correcte, le reçu présenté n'est pas valable.</x-cc.alerte>
    @else
        <div @class(['mb-6 flex items-center gap-4 rounded-2xl p-5 ring-2', 'bg-emerald-50 ring-emerald-600 text-emerald-950' => $valable, 'bg-red-50 ring-red-600 text-red-950' => ! $valable]) role="status">
            <x-cc.icon :name="$valable ? 'check-circle' : 'x-circle'" class="size-10 shrink-0" />
            <div>
                <p class="text-xl font-bold">{{ $valable ? 'Reçu valable' : 'Reçu non valable pour une collecte' }}</p>
                <p class="text-sm">
                    @if ($valable)
                        Paiement confirmé · abonnement actif · {{ $a->collectes_restantes }} collecte(s) restante(s).
                    @elseif ($p->statut !== 'confirme')
                        {{ config('cleancomores.paiements.'.$p->statut.'.label') }}.
                    @else
                        Abonnement : {{ mb_strtolower(config('cleancomores.abonnements.'.$a->statutEffectif().'.label')) }}.
                    @endif
                    @if ($viaQr) QR code authentique. @endif
                </p>
            </div>
        </div>
        @if ($p->simulation)
            <x-cc.alerte type="attention" class="mb-6">Paiement de <strong>démonstration</strong> (simulation), confirmé par un responsable{{ $p->traitePar ? ' : '.$p->traitePar->name : '' }}.</x-cc.alerte>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-cc.bloc titre="Reçu">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-zinc-600">Citoyen</dt><dd class="font-medium">{{ $a->citoyen->name }}</dd></div>
                        <div><dt class="text-zinc-600">Formule</dt><dd class="font-medium">{{ $a->formule_nom }}</dd></div>
                        <div><dt class="text-zinc-600">Montant</dt><dd class="font-medium tabular-nums">{{ number_format($p->montant, 0, ',', ' ') }} {{ $p->devise }}</dd></div>
                        <div><dt class="text-zinc-600">Date du paiement</dt><dd class="font-medium">{{ $p->confirme_at?->translatedFormat('j F Y à H:i') ?? '—' }}</dd></div>
                        <div><dt class="text-zinc-600">Statut du paiement</dt><dd><x-cc.etat groupe="paiements" :valeur="$p->statut" taille="sm" /></dd></div>
                        <div><dt class="text-zinc-600">Abonnement</dt><dd><x-cc.etat groupe="abonnements" :valeur="$a->statutEffectif()" taille="sm" /> <span class="text-zinc-600">{{ $a->fin_at ? 'jusqu\'au '.$a->fin_at->translatedFormat('j M Y') : '' }}</span></dd></div>
                        <div class="sm:col-span-2"><dt class="text-zinc-600">Adresse de collecte</dt><dd class="font-medium">{{ $a->adresse }}, {{ $a->commune }}</dd></div>
                    </dl>
                </x-cc.bloc>

                <x-cc.bloc titre="Collectes réalisées avec ce reçu" flush>
                    @php $faites = $p->collectes->where('statut', 'effectuee'); @endphp
                    @if ($faites->isEmpty())
                        <p class="p-4 text-sm text-zinc-600 sm:p-5">Aucune collecte effectuée pour le moment.</p>
                    @else
                        <ul class="divide-y divide-zinc-100 text-sm">
                            @foreach ($faites as $c)
                                <li class="px-4 py-3 sm:px-5">{{ $c->effectuee_at->translatedFormat('j F Y à H:i') }} — {{ $c->agent?->name ?? 'agent' }}</li>
                            @endforeach
                        </ul>
                    @endif
                </x-cc.bloc>
            </div>

            <x-cc.bloc titre="Collecte">
                @if (! $valable)
                    <p class="text-sm text-zinc-700">Aucune collecte ne peut être enregistrée avec ce reçu.</p>
                @elseif ($a->collecteEnAttente)
                    <p class="text-sm text-zinc-700">Le citoyen a demandé un passage ({{ config('cleancomores.creneaux.'.$a->collecteEnAttente->creneau) }}).</p>
                    <x-cc.bouton class="mt-4" :href="route('agent.collectes.show', $a->collecteEnAttente)" icone="truck" pleine>Ouvrir la demande</x-cc.bouton>
                @else
                    <p class="text-sm text-zinc-700">Pas de demande en attente. Si vous venez de ramasser les déchets de ce citoyen, enregistrez la collecte :</p>
                    <form id="form-collecter" method="POST" action="{{ route('agent.recus.collecter', $p->reference) }}" class="mt-4 space-y-4">
                        @csrf
                        <x-cc.champ label="Note de passage" pour="note_agent">
                            <textarea id="note_agent" name="note_agent" rows="2" maxlength="500" class="cc-input"></textarea>
                        </x-cc.champ>
                        <x-cc.bouton variante="succes" data-ouvrir="dialogue-collecter" icone="check" pleine>Collecte effectuée</x-cc.bouton>
                    </form>
                    <x-cc.dialogue id="dialogue-collecter" titre="Enregistrer la collecte ?" confirmer="Oui, c'est fait" ton="succes" formulaire="form-collecter">
                        Une collecte sera décomptée ({{ $a->collectes_restantes }} restante(s)). Cette action est définitive.
                    </x-cc.dialogue>
                @endif
            </x-cc.bloc>
        </div>
    @endif
</x-layouts::espace>
