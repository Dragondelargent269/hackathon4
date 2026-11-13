{{--
    Détail d'une mission (agent). Variable : $mission (même forme qu'un signalement)
    - affecte  → bouton « Prendre en charge » (POST agent.missions.demarrer)
    - en_cours → compte rendu + photo de preuve facultative (POST multipart agent.missions.terminer)
    L'agent ne clôture jamais : il transmet la fin d'intervention, le responsable valide.
    Les coordonnées du citoyen ne sont pas affichées à l'agent.
--}}
@php $m = $mission; @endphp
<x-layouts::espace role="agent" :title="'Mission '.$m->reference">
    <x-cc.entete-page :titre="config('cleancomores.categories.'.$m->categorie.'.label')" :retour="route('agent.missions.index')" retour-label="Mes missions">
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm text-zinc-600">{{ $m->reference }}</span>
            <x-cc.statut :statut="$m->statut" />
            <x-cc.priorite :priorite="$m->priorite" />
        </div>
    </x-cc.entete-page>

    <div class="space-y-6">
        @if ($m->consignes)
            <x-cc.alerte type="attention" titre="Consignes du responsable">{{ $m->consignes }}</x-cc.alerte>
        @endif

        <x-cc.bloc titre="Où intervenir">
            <p class="flex items-start gap-2 font-medium"><x-cc.icon name="map-pin" class="mt-0.5 size-5 text-lagon-700" />{{ $m->commune }}@if ($m->repere) — {{ $m->repere }}@endif</p>
            @if ($m->latitude && $m->longitude)
                <x-cc.carte class="mt-4" hauteur="h-60" :points="[['lat' => $m->latitude, 'lng' => $m->longitude, 'titre' => $m->repere ?? $m->commune, 'statut' => $m->statut]]" label="Lieu de l'intervention" />
                <x-cc.bouton variante="secondaire" icone="map-pin" class="mt-3 max-sm:w-full" href="https://www.openstreetmap.org/directions?to={{ $m->latitude }}%2C{{ $m->longitude }}" target="_blank" rel="noopener">
                    Itinéraire<span class="sr-only"> (nouvel onglet)</span>
                </x-cc.bouton>
            @endif
        </x-cc.bloc>

        <x-cc.bloc titre="Signalement">
            <p class="whitespace-pre-line text-sm">{{ $m->description }}</p>
            <div class="mt-4"><x-cc.photo :url="$m->photo_url" /></div>
        </x-cc.bloc>

        @if ($m->statut === 'affecte')
            <form method="POST" action="{{ route('agent.missions.demarrer', $m->id) }}" class="sticky bottom-20 z-10 lg:static">
                @csrf
                <x-cc.bouton type="submit" icone="truck" pleine class="py-3 text-base shadow-lg lg:shadow-sm">Prendre en charge — je pars sur place</x-cc.bouton>
            </form>
        @elseif ($m->statut === 'en_cours')
            <x-cc.bloc titre="Compte rendu d'intervention" description="Décrivez ce qui a été fait. Le responsable validera la clôture.">
                <form method="POST" action="{{ route('agent.missions.terminer', $m->id) }}" enctype="multipart/form-data" class="space-y-5" data-confirmer="dialogue-terminer">
                    @csrf
                    <x-cc.champ label="Ce qui a été fait" pour="compte_rendu" requis aide="Quantité collectée, état du lieu, difficultés rencontrées…">
                        <textarea id="compte_rendu" name="compte_rendu" rows="5" class="cc-input" required minlength="10" aria-describedby="compte_rendu-aide">{{ old('compte_rendu') }}</textarea>
                    </x-cc.champ>
                    <div class="space-y-2">
                        <x-cc.champ label="Photo de preuve" pour="photo_preuve" aide="Une photo « après » aide à valider rapidement.">
                            <label for="photo_preuve" class="flex min-h-20 cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 bg-sable-50 p-4 text-sm font-medium hover:border-lagon-600">
                                <x-cc.icon name="camera" class="size-6 text-lagon-700" />Prendre une photo
                            </label>
                            <input id="photo_preuve" name="photo_preuve" type="file" accept="image/*" capture="environment" class="sr-only" data-apercu="preuve-apercu" aria-describedby="photo_preuve-aide">
                        </x-cc.champ>
                        <div id="preuve-apercu" hidden class="flex items-center gap-3 rounded-xl border border-zinc-200 p-2">
                            <img alt="Aperçu de la photo de preuve" class="size-20 rounded-lg object-cover">
                            <span class="min-w-0 flex-1 truncate text-sm" data-nom-fichier></span>
                            <x-cc.bouton variante="fantome" data-retirer-photo="photo_preuve" icone="x">Retirer</x-cc.bouton>
                        </div>
                    </div>
                    <x-cc.bouton type="submit" icone="send" pleine class="text-base">Transmettre la fin d'intervention</x-cc.bouton>
                </form>
            </x-cc.bloc>

            <x-cc.dialogue id="dialogue-terminer" titre="Transmettre la fin d'intervention ?" confirmer="Oui, transmettre">
                <p>Le dossier passera au statut <strong>Résolu</strong> et sera envoyé au responsable pour validation. Vous ne pourrez plus modifier le compte rendu.</p>
            </x-cc.dialogue>
        @else
            <x-cc.alerte type="succes" titre="Intervention transmise">Ce dossier est entre les mains du responsable.</x-cc.alerte>
        @endif

        <x-cc.bloc titre="Historique">
            <x-cc.historique :etapes="$m->historique" />
        </x-cc.bloc>
    </div>
</x-layouts::espace>
