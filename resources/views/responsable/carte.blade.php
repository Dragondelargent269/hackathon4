{{-- Carte complète. Variables : $points, $filtres (zone, etat, categorie, couches), $compteurs (par type) --}}
<x-layouts::espace role="responsable" title="Carte" large>
    <x-cc.entete-page titre="Carte" sous-titre="Signalements, points de collecte et collectes à domicile en attente." />

    <form method="GET" class="mb-4 rounded-xl border border-zinc-200 bg-white p-4" aria-label="Filtres de la carte">
        <input type="hidden" name="f" value="1">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-cc.champ label="Île" pour="zone">
                <select id="zone" name="zone" class="cc-input">
                    <option value="">Tout l'archipel</option>
                    @foreach (config('cleancomores.zones') as $cle => $z)
                        <option value="{{ $cle }}" @selected($filtres['zone'] === $cle)>{{ $z['label'] }}</option>
                    @endforeach
                </select>
            </x-cc.champ>
            <x-cc.champ label="État du signalement" pour="etat">
                <select id="etat" name="etat" class="cc-input">
                    <option value="">Tous</option>
                    @foreach (config('cleancomores.marqueurs') as $cle => $g)
                        <option value="{{ $cle }}" @selected($filtres['etat'] === $cle)>{{ $g['label'] }}</option>
                    @endforeach
                </select>
            </x-cc.champ>
            <x-cc.champ label="Catégorie" pour="categorie">
                <select id="categorie" name="categorie" class="cc-input">
                    <option value="">Toutes</option>
                    @foreach (config('cleancomores.categories') as $cle => $c)
                        <option value="{{ $cle }}" @selected($filtres['categorie'] === $cle)>{{ $c['label'] }}</option>
                    @endforeach
                </select>
            </x-cc.champ>
        </div>
        <fieldset class="mt-4">
            <legend class="text-sm font-medium text-zinc-900">Afficher</legend>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2">
                @foreach (['signalements' => 'Signalements', 'point_collecte' => 'Points de collecte', 'collecte_domicile' => 'Collectes à domicile en attente'] as $cle => $label)
                    <label class="flex min-h-11 items-center gap-2 text-sm">
                        <input type="checkbox" name="couches[]" value="{{ $cle }}" class="size-5 rounded accent-lagon-700" @checked(in_array($cle, $filtres['couches'], true))>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <x-cc.bouton type="submit" icone="filter">Appliquer</x-cc.bouton>
            <x-cc.bouton variante="fantome" :href="route('responsable.carte')">Réinitialiser</x-cc.bouton>
            <p class="text-sm text-zinc-600" role="status">
                {{ $compteurs['signalement'] ?? 0 }} signalement(s) · {{ $compteurs['point_collecte'] ?? 0 }} point(s) de collecte · {{ $compteurs['collecte_domicile'] ?? 0 }} collecte(s) à domicile
            </p>
        </div>
    </form>

    <x-cc.carte :points="$points" hauteur="h-[65vh] min-h-96" label="Carte des signalements et des interventions" legende>
        <p class="mt-2">{{ $points->count() }} élément(s) à afficher.</p>
    </x-cc.carte>
    <p class="mt-3 text-sm text-zinc-600">Les points de collecte se gèrent dans l'<a href="{{ url('/admin') }}" class="font-medium text-lagon-800 underline">administration</a>.</p>
</x-layouts::espace>
