{{-- Collecte à domicile : offres + abonnements du citoyen. Variables : $formules, $abonnements --}}
<x-layouts::espace role="citoyen" title="Collecte à domicile">
    <x-cc.entete-page titre="Collecte à domicile" sous-titre="Vous ne pouvez pas vous déplacer jusqu'à un point de dépôt ? Une équipe passe chez vous.">
        <x-slot:actions>
            <x-cc.bouton variante="secondaire" :href="route('citoyen.paiements.index')" icone="receipt">Mes paiements</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    <x-cc.alerte type="info" class="mb-6">Signaler un dépôt sauvage reste <strong>gratuit</strong>. Seule la collecte à votre adresse est payante.</x-cc.alerte>

    @if ($abonnements->isNotEmpty())
        <h2 class="mb-3 text-lg font-semibold">Mes abonnements</h2>
        <ul class="mb-8 space-y-3">
            @foreach ($abonnements as $a)
                @php $etat = $a->statutEffectif(); @endphp
                <li>
                    <a href="{{ route('citoyen.abonnements.show', $a) }}" class="flex flex-wrap items-center gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs hover:border-lagon-600">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-lagon-50 text-lagon-800"><x-cc.icon name="truck" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $a->formule_nom }}</span>
                            <span class="block truncate text-sm text-zinc-600">{{ $a->reference }} · {{ $a->adresse }}, {{ $a->commune }}</span>
                            @if ($etat === 'actif')
                                <span class="block text-sm text-zinc-700">{{ $a->collectes_restantes }} collecte(s) restante(s) · jusqu'au {{ $a->fin_at->translatedFormat('j F Y') }}</span>
                            @endif
                        </span>
                        <span class="flex flex-col items-end gap-1">
                            <x-cc.etat groupe="abonnements" :valeur="$etat" taille="sm" />
                            @if ($a->collecteEnAttente)<x-cc.etat groupe="collectes" valeur="demandee" taille="sm" />@endif
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="mb-3 text-lg font-semibold">Nos formules</h2>
    @if ($formules->isEmpty())
        <x-cc.vide titre="Aucune formule disponible" icone="truck">Les offres de collecte à domicile seront bientôt proposées.</x-cc.vide>
    @else
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ($formules as $f)
                <article class="flex flex-col rounded-2xl border border-zinc-200 bg-white p-5 shadow-xs">
                    <h3 class="text-lg font-semibold">{{ $f->nom }}</h3>
                    <p class="mt-1 text-3xl font-bold tabular-nums text-lagon-900">{{ number_format($f->prix, 0, ',', ' ') }} <span class="text-base font-semibold">KMF</span></p>
                    <p class="text-sm text-zinc-600">{{ $f->collectes_incluses }} collecte(s) · valable {{ $f->duree_jours }} jours</p>
                    @if ($f->description)<p class="mt-3 text-sm text-zinc-700">{{ $f->description }}</p>@endif
                    @if (! empty($f->services))
                        <ul class="mt-3 flex-1 space-y-1.5 text-sm">
                            @foreach ($f->services as $service)
                                <li class="flex gap-2"><x-cc.icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-700" />{{ $service }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <x-cc.bouton :href="route('citoyen.abonnements.create', $f)" class="mt-5" pleine>Choisir cette formule</x-cc.bouton>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts::espace>
