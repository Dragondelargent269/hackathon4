{{-- Reçu numérique (imprimable). Variable : $paiement (abonnement, citoyen, collectes) --}}
@php
    $p = $paiement;
    $a = $p->abonnement;
    $confirme = $p->statut === 'confirme';
@endphp
<x-layouts::espace role="citoyen" title="Reçu {{ $p->reference }}">
    <x-cc.entete-page :titre="$confirme ? 'Reçu de paiement' : 'Demande de paiement'" :retour="route('citoyen.abonnements.show', $a)" retour-label="Mon abonnement" data-pas-imprimer>
        <x-slot:actions>
            @if ($confirme)
                <x-cc.bouton variante="secondaire" icone="printer" onclick="window.print()">Imprimer</x-cc.bouton>
            @endif
        </x-slot:actions>
    </x-cc.entete-page>

    @if ($p->statut === 'en_attente')
        <x-cc.alerte type="attention" titre="Paiement en attente de confirmation" class="mb-6" data-pas-imprimer>
            @if ($p->simulation)
                Mode démonstration : un responsable CleanComores doit confirmer ce paiement. Ce document n'est <strong>pas encore</strong> une preuve de paiement.
            @else
                Validez le paiement sur votre téléphone. Ce document deviendra un reçu dès la confirmation de l'opérateur.
            @endif
        </x-cc.alerte>
        <form method="POST" action="{{ route('citoyen.paiements.annuler', $p->reference) }}" data-confirmer="annuler-paiement" class="mb-6" data-pas-imprimer>
            @csrf
            <x-cc.bouton type="submit" variante="secondaire" icone="x">Annuler cette demande</x-cc.bouton>
        </form>
        <x-cc.dialogue id="annuler-paiement" titre="Annuler la demande de paiement ?" confirmer="Oui, annuler" ton="danger">Vous pourrez relancer un paiement depuis votre abonnement.</x-cc.dialogue>
    @elseif (in_array($p->statut, ['echoue', 'annule'], true))
        <x-cc.alerte type="erreur" :titre="config('cleancomores.paiements.'.$p->statut.'.label')" class="mb-6" data-pas-imprimer>
            {{ $p->motif_refus }} Ce document n'est pas une preuve de paiement.
        </x-cc.alerte>
    @endif

    <article class="mx-auto max-w-2xl rounded-2xl border border-zinc-300 bg-white p-6 shadow-xs print:border-0 print:shadow-none sm:p-8" aria-labelledby="recu-titre">
        <header class="flex items-start justify-between gap-4 border-b border-dashed border-zinc-300 pb-5">
            <div>
                <x-cc.logo />
                <h2 id="recu-titre" class="mt-3 text-sm font-semibold uppercase tracking-wide text-zinc-600">{{ $confirme ? 'Reçu de paiement' : 'Demande de paiement — non valable comme reçu' }}</h2>
                <p class="mt-1 font-mono text-2xl font-bold tracking-wider">{{ $p->reference }}</p>
            </div>
            <x-cc.etat groupe="paiements" :valeur="$p->statut" />
        </header>

        @if ($p->simulation)
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-center text-sm font-semibold text-amber-900 ring-1 ring-amber-300">SIMULATION — paiement de démonstration, aucun argent réel</p>
        @endif

        <dl class="mt-5 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
            <div><dt class="text-zinc-600">Citoyen</dt><dd class="font-medium">{{ $p->citoyen->name }}</dd></div>
            <div><dt class="text-zinc-600">Formule</dt><dd class="font-medium">{{ $a->formule_nom }} ({{ $a->collectes_incluses }} collecte(s), {{ $a->duree_jours }} jours)</dd></div>
            <div><dt class="text-zinc-600">Montant</dt><dd class="text-lg font-semibold tabular-nums">{{ number_format($p->montant, 0, ',', ' ') }} {{ $p->devise }}</dd></div>
            <div><dt class="text-zinc-600">{{ $confirme ? 'Date du paiement' : 'Date de la demande' }}</dt><dd class="font-medium">{{ ($p->confirme_at ?? $p->created_at)->translatedFormat('j F Y à H:i') }}</dd></div>
            <div><dt class="text-zinc-600">Statut du paiement</dt><dd class="font-medium">{{ config('cleancomores.paiements.'.$p->statut.'.label') }}</dd></div>
            <div><dt class="text-zinc-600">Abonnement</dt><dd class="font-medium">{{ $a->reference }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-zinc-600">Adresse de collecte</dt><dd class="font-medium">{{ $a->adresse }}, {{ $a->commune }}</dd></div>
            @if ($confirme && $a->fin_at)
                <div class="sm:col-span-2"><dt class="text-zinc-600">Valable jusqu'au</dt><dd class="font-medium">{{ $a->fin_at->translatedFormat('j F Y') }} · {{ $a->collectes_restantes }} collecte(s) restante(s)</dd></div>
            @endif
        </dl>

        @if ($confirme)
            <div class="mt-6 flex flex-col items-center gap-3 border-t border-dashed border-zinc-300 pt-6 sm:flex-row sm:items-start">
                <div class="shrink-0 rounded-lg bg-white p-2 ring-1 ring-zinc-200 [&_svg]:size-40" role="img" aria-label="QR code de vérification du reçu {{ $p->reference }}">{!! $p->qrSvg(160) !!}</div>
                <p class="text-sm text-zinc-700">
                    <strong>Présentez ce QR code à l'agent de collecte.</strong> Il le scanne ou saisit la référence <span class="font-mono font-semibold">{{ $p->reference }}</span> :
                    l'application lui affiche l'état réel de votre paiement enregistré par CleanComores. Une capture d'écran seule n'est pas une preuve.
                </p>
            </div>
        @endif
    </article>
</x-layouts::espace>
