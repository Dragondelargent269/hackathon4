{{-- Liste des signalements (responsable).
     Variables : $signalements (itérable ou paginator), $total (int, optionnel)
     Filtres GET : q, statut, priorite, categorie, zone --}}
@php
    $liste = collect(method_exists($signalements, 'items') ? $signalements->items() : $signalements);
    $filtresActifs = collect(request()->only(['q', 'statut', 'priorite', 'categorie', 'zone']))->filter()->count();
@endphp
<x-layouts::espace role="responsable" title="Signalements" large>
    <x-cc.entete-page titre="Signalements" sous-titre="Recherchez, filtrez et ouvrez un dossier pour le traiter." />

    {{-- Barre de filtres --}}
    <form method="GET" action="{{ route('responsable.signalements.index') }}" data-filtres-auto class="mb-5 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs" role="search" aria-label="Filtrer les signalements">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
            <div class="sm:col-span-2">
                <label for="q" class="sr-only">Rechercher</label>
                <div class="relative">
                    <x-cc.icon name="search" class="pointer-events-none absolute start-3 top-3 size-5 text-zinc-400" />
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Référence, lieu, mot-clé…" class="cc-input ps-10">
                </div>
            </div>
            @foreach ([
                'statut' => ['Statut', collect(config('cleancomores.statuts'))->map(fn ($s) => $s['label'])],
                'priorite' => ['Priorité', collect(config('cleancomores.priorites'))->map(fn ($p) => $p['label'])],
                'categorie' => ['Catégorie', collect(config('cleancomores.categories'))->map(fn ($c) => $c['label'])],
                'zone' => ['Zone', collect(config('cleancomores.zones'))->map(fn ($z) => $z['label'])],
            ] as $nom => [$label, $options])
                <div>
                    <label for="f-{{ $nom }}" class="sr-only">{{ $label }}</label>
                    <select id="f-{{ $nom }}" name="{{ $nom }}" class="cc-input">
                        <option value="">{{ $label }} : tous</option>
                        @foreach ($options as $val => $lib)
                            <option value="{{ $val }}" @selected(request($nom) === $val)>{{ $lib }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-zinc-700" role="status">
                <strong>{{ method_exists($signalements, 'total') ? $signalements->total() : $liste->count() }}</strong> résultat(s)
                @isset($total) sur {{ $total }}@endisset
                @if ($filtresActifs) · {{ $filtresActifs }} filtre(s) actif(s)@endif
            </p>
            <div class="flex gap-2">
                @if ($filtresActifs)<x-cc.bouton variante="fantome" :href="route('responsable.signalements.index')" icone="x">Effacer les filtres</x-cc.bouton>@endif
                <x-cc.bouton type="submit" variante="secondaire" icone="filter">Appliquer</x-cc.bouton>
            </div>
        </div>
    </form>

    @if ($liste->isEmpty())
        <x-cc.vide titre="Aucun signalement ne correspond" icone="search">Essayez d'élargir votre recherche ou d'effacer certains filtres.</x-cc.vide>
    @else
        {{-- Tableau (ordinateur) --}}
        <div class="hidden overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs md:block">
            <table class="w-full text-sm">
                <caption class="sr-only">Liste des signalements</caption>
                <thead class="bg-zinc-50 text-start text-xs uppercase tracking-wide text-zinc-600">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Dossier</th>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Lieu</th>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Statut</th>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Priorité</th>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Agent</th>
                        <th scope="col" class="px-4 py-3 text-start font-semibold">Mis à jour</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($liste as $s)
                        <tr class="hover:bg-zinc-50">
                            <td class="px-4 py-3">
                                <span class="block font-medium"><x-cc.categorie :categorie="$s->categorie" /></span>
                                <span class="font-mono text-xs text-zinc-600">{{ $s->reference }}</span>
                            </td>
                            <td class="px-4 py-3"><span class="block">{{ $s->commune }}</span><span class="block max-w-52 truncate text-xs text-zinc-600">{{ $s->repere }}</span></td>
                            <td class="px-4 py-3"><x-cc.statut :statut="$s->statut" taille="sm" /></td>
                            <td class="px-4 py-3"><x-cc.priorite :priorite="$s->priorite" /></td>
                            <td class="px-4 py-3">{{ $s->agent?->name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-zinc-600"><time datetime="{{ $s->updated_at->toIso8601String() }}">{{ $s->updated_at->diffForHumans() }}</time></td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('responsable.signalements.show', $s->id) }}" class="cc-touch inline-flex items-center gap-1 rounded-lg px-3 font-semibold text-lagon-800 hover:bg-lagon-50">
                                    Ouvrir<span class="sr-only"> le dossier {{ $s->reference }}</span><x-cc.icon name="chevron-right" class="size-4" />
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Cartes (mobile) --}}
        <ul class="space-y-3 md:hidden">
            @foreach ($liste as $s)
                <x-cc.signalement-ligne :signalement="$s" :href="route('responsable.signalements.show', $s->id)" avec-priorite avec-agent />
            @endforeach
        </ul>

        @if (method_exists($signalements, 'links'))<div class="mt-6">{{ $signalements->withQueryString()->links() }}</div>@endif
    @endif
</x-layouts::espace>
