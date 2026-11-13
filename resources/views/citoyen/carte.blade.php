{{-- Carte des signalements et points de collecte. Variables : $points, $zone, $nbSignalements --}}
<x-layouts::espace role="citoyen" title="Carte" large>
    <x-cc.entete-page titre="Carte de la propreté" sous-titre="Signalements de tous les citoyens et points de collecte. Touchez un repère pour en savoir plus.">
        <x-slot:actions>
            <x-cc.bouton :href="route('citoyen.signalements.create')" icone="plus">Signaler un problème</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    <form method="GET" data-filtres-auto class="mb-4 flex flex-wrap items-end gap-3">
        <x-cc.champ label="Île" pour="zone" class="w-full sm:w-64">
            <select id="zone" name="zone" class="cc-input">
                <option value="">Tout l'archipel</option>
                @foreach (config('cleancomores.zones') as $cle => $z)
                    <option value="{{ $cle }}" @selected($zone === $cle)>{{ $z['label'] }}</option>
                @endforeach
            </select>
        </x-cc.champ>
        <noscript><x-cc.bouton type="submit" variante="secondaire">Filtrer</x-cc.bouton></noscript>
        <p class="pb-2 text-sm text-zinc-600" role="status">{{ $nbSignalements }} signalement(s) affiché(s)</p>
    </form>

    <x-cc.carte :points="$points" hauteur="h-[60vh] min-h-80" label="Carte des signalements et des points de collecte" legende>
        <p class="mt-2">{{ $nbSignalements }} signalement(s) sur la carte.</p>
    </x-cc.carte>
    <p class="mt-3 text-sm text-zinc-600">Les signalements rejetés ne sont pas affichés. Seuls vos propres signalements sont consultables en détail.</p>
</x-layouts::espace>
