{{-- Détail d'un abonnement. Variable : $abonnement (paiements, collectes, paiementConfirme, collecteEnAttente) --}}
@php
    $a = $abonnement;
    $etat = $a->statutEffectif();
    $paiementEnAttente = $a->paiements->firstWhere('statut', 'en_attente');
@endphp
<x-layouts::espace role="citoyen" title="Abonnement {{ $a->reference }}">
    <x-cc.entete-page :titre="$a->formule_nom" :sous-titre="$a->reference" :retour="route('citoyen.abonnements.index')" retour-label="Collecte à domicile">
        <x-slot:actions><x-cc.etat groupe="abonnements" :valeur="$etat" taille="lg" /></x-slot:actions>
    </x-cc.entete-page>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if ($etat === 'en_attente_paiement')
                <x-cc.bloc titre="Paiement">
                    @if ($paiementEnAttente)
                        <x-cc.alerte type="attention" titre="Paiement en attente de confirmation">
                            Demande {{ $paiementEnAttente->reference }} du {{ $paiementEnAttente->created_at->translatedFormat('j F Y à H:i') }}.
                            L'abonnement sera actif dès la confirmation.
                        </x-cc.alerte>
                        <x-cc.bouton variante="secondaire" class="mt-4" :href="route('citoyen.paiements.show', $paiementEnAttente->reference)" icone="receipt">Voir la demande de paiement</x-cc.bouton>
                    @else
                        <p class="text-sm text-zinc-700">Aucun paiement en cours. Le dernier a été refusé ou annulé.</p>
                        <form method="POST" action="{{ route('citoyen.abonnements.payer', $a) }}" class="mt-4">
                            @csrf
                            <x-cc.bouton type="submit" icone="refresh">Relancer le paiement ({{ number_format($a->prix, 0, ',', ' ') }} KMF)</x-cc.bouton>
                        </form>
                    @endif
                </x-cc.bloc>
            @endif

            @if ($etat === 'actif')
                <x-cc.bloc titre="Demander un passage">
                    @if ($a->collecteEnAttente)
                        <x-cc.alerte type="info" titre="Collecte demandée le {{ $a->collecteEnAttente->created_at->translatedFormat('j F à H:i') }}">
                            Créneau : {{ config('cleancomores.creneaux.'.$a->collecteEnAttente->creneau) }}. Préparez vos déchets et votre reçu : l'agent le vérifiera.
                        </x-cc.alerte>
                        <form method="POST" action="{{ route('citoyen.collectes.annuler', $a->collecteEnAttente) }}" data-confirmer="annuler-collecte" class="mt-4">
                            @csrf
                            <x-cc.bouton type="submit" variante="secondaire" icone="x">Annuler la demande</x-cc.bouton>
                        </form>
                        <x-cc.dialogue id="annuler-collecte" titre="Annuler la demande de collecte ?" confirmer="Oui, annuler" ton="danger">Aucune collecte ne sera décomptée.</x-cc.dialogue>
                    @else
                        <form method="POST" action="{{ route('citoyen.collectes.store', $a) }}" class="space-y-4">
                            @csrf
                            <x-cc.champ label="Créneau souhaité" pour="creneau" requis>
                                <select id="creneau" name="creneau" class="cc-input" required>
                                    @foreach (config('cleancomores.creneaux') as $cle => $label)
                                        <option value="{{ $cle }}" @selected(old('creneau', 'indifferent') === $cle)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </x-cc.champ>
                            <x-cc.champ label="Précision pour l'agent" pour="note_citoyen" aide="Ex. : 3 sacs devant le portail.">
                                <textarea id="note_citoyen" name="note_citoyen" rows="2" maxlength="500" class="cc-input" aria-describedby="note_citoyen-aide">{{ old('note_citoyen') }}</textarea>
                            </x-cc.champ>
                            <x-cc.bouton type="submit" icone="truck">Demander une collecte</x-cc.bouton>
                        </form>
                    @endif
                </x-cc.bloc>
            @endif

            <x-cc.bloc titre="Historique des collectes" flush>
                @if ($a->collectes->isEmpty())
                    <p class="p-4 text-sm text-zinc-600 sm:p-5">Aucune collecte pour le moment.</p>
                @else
                    <ul class="divide-y divide-zinc-100">
                        @foreach ($a->collectes as $c)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 sm:px-5">
                                <span class="text-sm">
                                    <span class="block font-medium">Demandée le {{ $c->created_at->translatedFormat('j F Y à H:i') }}</span>
                                    @if ($c->effectuee_at)<span class="block text-zinc-600">Effectuée le {{ $c->effectuee_at->translatedFormat('j F Y à H:i') }}{{ $c->agent ? ' par '.$c->agent->name : '' }}</span>@endif
                                    @if ($c->note_agent)<span class="block text-zinc-600">« {{ $c->note_agent }} »</span>@endif
                                </span>
                                <x-cc.etat groupe="collectes" :valeur="$c->statut" taille="sm" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cc.bloc>
        </div>

        <div class="space-y-6">
            <x-cc.bloc titre="Détails">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-zinc-600">Collectes restantes</dt><dd class="text-2xl font-semibold tabular-nums">{{ $a->statut === 'actif' ? $a->collectes_restantes : '—' }} <span class="text-sm font-normal text-zinc-600">/ {{ $a->collectes_incluses }}</span></dd></div>
                    <div><dt class="text-zinc-600">Validité</dt><dd>{{ $a->debut_at ? 'Du '.$a->debut_at->translatedFormat('j M Y').' au '.$a->fin_at->translatedFormat('j M Y') : 'Commence à la confirmation du paiement ('.$a->duree_jours.' jours)' }}</dd></div>
                    <div><dt class="text-zinc-600">Adresse</dt><dd>{{ $a->adresse }}, {{ $a->commune }}@if ($a->repere)<br><span class="text-zinc-600">{{ $a->repere }}</span>@endif</dd></div>
                    <div><dt class="text-zinc-600">Téléphone</dt><dd>{{ $a->telephone }}</dd></div>
                </dl>
                <x-cc.carte class="mt-4" hauteur="h-48" :points="[$a->pourCarte()]" label="Position du domicile" />
            </x-cc.bloc>

            <x-cc.bloc titre="Paiements et reçus" flush>
                <ul class="divide-y divide-zinc-100">
                    @foreach ($a->paiements as $p)
                        <li>
                            <a href="{{ route('citoyen.paiements.show', $p->reference) }}" class="flex items-center justify-between gap-2 px-4 py-3 text-sm hover:bg-zinc-50 sm:px-5">
                                <span><span class="block font-mono font-medium">{{ $p->reference }}</span><span class="block text-zinc-600">{{ $p->created_at->translatedFormat('j M Y') }}</span></span>
                                <x-cc.etat groupe="paiements" :valeur="$p->statut" taille="sm" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </x-cc.bloc>
        </div>
    </div>
</x-layouts::espace>
