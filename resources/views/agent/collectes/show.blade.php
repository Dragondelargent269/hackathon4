{{-- Détail d'une collecte à domicile. Variable : $collecte (abonnement.citoyen, paiement, agent) --}}
@php
    $c = $collecte;
    $a = $c->abonnement;
    $p = $c->paiement;
    $valable = $a->estUtilisable() && $p->statut === 'confirme';
@endphp
<x-layouts::espace role="agent" title="Collecte — {{ $a->adresse }}">
    <x-cc.entete-page :titre="$a->adresse" :sous-titre="$a->commune.' · '.$a->citoyen->name" :retour="route('agent.collectes.index')" retour-label="Collectes à domicile">
        <x-slot:actions><x-cc.etat groupe="collectes" :valeur="$c->statut" taille="lg" /></x-slot:actions>
    </x-cc.entete-page>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-cc.bloc titre="Lieu de collecte">
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-600">Citoyen</dt><dd class="font-medium">{{ $a->citoyen->name }}</dd></div>
                    <div><dt class="text-zinc-600">Téléphone</dt><dd class="font-medium"><a href="tel:{{ $a->telephone }}" class="text-lagon-800 underline">{{ $a->telephone }}</a></dd></div>
                    <div><dt class="text-zinc-600">Adresse</dt><dd class="font-medium">{{ $a->adresse }}, {{ $a->commune }}</dd></div>
                    <div><dt class="text-zinc-600">Repère</dt><dd class="font-medium">{{ $a->repere ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-600">Créneau</dt><dd class="font-medium">{{ config('cleancomores.creneaux.'.$c->creneau) }}</dd></div>
                    <div><dt class="text-zinc-600">GPS</dt><dd class="font-mono">{{ $a->latitude }}, {{ $a->longitude }}
                        · <a class="font-sans text-lagon-800 underline" href="https://www.openstreetmap.org/?mlat={{ $a->latitude }}&mlon={{ $a->longitude }}#map=18/{{ $a->latitude }}/{{ $a->longitude }}" target="_blank" rel="noopener">Itinéraire</a></dd></div>
                    @if ($c->note_citoyen)<div class="sm:col-span-2"><dt class="text-zinc-600">Précision du citoyen</dt><dd>« {{ $c->note_citoyen }} »</dd></div>@endif
                </dl>
                <x-cc.carte class="mt-4" hauteur="h-64" :points="[$a->pourCarte()]" label="Position du domicile" legende />
            </x-cc.bloc>

            @if ($c->statut === 'demandee')
                <x-cc.bloc titre="Confirmer la collecte">
                    @if ($valable)
                        <p class="text-sm text-zinc-700">Vérifiez d'abord le reçu présenté par le citoyen (QR code ou référence <span class="font-mono font-semibold">{{ $p->reference }}</span>), puis confirmez une fois les déchets ramassés.</p>
                        <form id="form-confirmer" method="POST" action="{{ route('agent.collectes.confirmer', $c) }}" class="mt-4 space-y-4">
                            @csrf
                            <x-cc.champ label="Note de passage" pour="note_agent" aide="Ex. : 3 sacs récupérés.">
                                <textarea id="note_agent" name="note_agent" rows="2" maxlength="500" class="cc-input" aria-describedby="note_agent-aide">{{ old('note_agent') }}</textarea>
                            </x-cc.champ>
                            <x-cc.bouton variante="succes" data-ouvrir="dialogue-confirmer" icone="check">Collecte effectuée</x-cc.bouton>
                        </form>
                        <x-cc.dialogue id="dialogue-confirmer" titre="Confirmer la collecte ?" confirmer="Oui, c'est fait" ton="succes" formulaire="form-confirmer">
                            Une collecte sera décomptée de l'abonnement de {{ $a->citoyen->name }} ({{ $a->collectes_restantes }} restante(s)). Cette action est définitive.
                        </x-cc.dialogue>
                    @else
                        <x-cc.alerte type="erreur" titre="Collecte impossible">L'abonnement n'est plus valable ({{ config('cleancomores.abonnements.'.$a->statutEffectif().'.label') }}) ou le paiement n'est pas confirmé.</x-cc.alerte>
                    @endif
                </x-cc.bloc>
            @else
                <x-cc.alerte type="succes" titre="Collecte effectuée le {{ $c->effectuee_at?->translatedFormat('j F Y à H:i') }}">
                    Par {{ $c->agent?->name }}.@if ($c->note_agent) « {{ $c->note_agent }} »@endif
                </x-cc.alerte>
            @endif
        </div>

        <x-cc.bloc titre="Reçu et abonnement">
            <dl class="space-y-3 text-sm">
                <div><dt class="text-zinc-600">Reçu</dt><dd><a class="font-mono font-semibold text-lagon-800 underline" href="{{ route('agent.recus.show', $p->reference) }}">{{ $p->reference }}</a></dd></div>
                <div><dt class="text-zinc-600">Paiement</dt><dd><x-cc.etat groupe="paiements" :valeur="$p->statut" taille="sm" />@if ($p->simulation) <span class="text-xs text-amber-800">(simulation)</span>@endif</dd></div>
                <div><dt class="text-zinc-600">Formule</dt><dd class="font-medium">{{ $a->formule_nom }} · {{ number_format($p->montant, 0, ',', ' ') }} KMF</dd></div>
                <div><dt class="text-zinc-600">Abonnement</dt><dd><x-cc.etat groupe="abonnements" :valeur="$a->statutEffectif()" taille="sm" /></dd></div>
                <div><dt class="text-zinc-600">Collectes restantes</dt><dd class="text-xl font-semibold tabular-nums">{{ $a->collectes_restantes }} / {{ $a->collectes_incluses }}</dd></div>
                <div><dt class="text-zinc-600">Valable jusqu'au</dt><dd>{{ $a->fin_at?->translatedFormat('j F Y') ?? '—' }}</dd></div>
            </dl>
        </x-cc.bloc>
    </div>
</x-layouts::espace>
