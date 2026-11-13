{{-- Vue d'ensemble du responsable.
     Variables : $indicateurs['a_traiter','en_intervention','a_valider','taux_resolution'],
                 $urgents, $a_valider, $nouveaux, $carte (itérables de signalements) --}}
<x-layouts::espace role="responsable" title="Vue d'ensemble" large>
    <x-cc.entete-page titre="Vue d'ensemble" :sous-titre="'Bonjour '.(($utilisateur ?? auth()->user())->name ?? '').' — voici l\'état des signalements aujourd\'hui.'">
        <x-slot:actions>
            <x-cc.bouton variante="secondaire" icone="chart" :href="route('responsable.statistiques')">Statistiques</x-cc.bouton>
            <x-cc.bouton icone="list" :href="route('responsable.signalements.index')">Tous les dossiers</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-cc.indicateur label="À vérifier" :valeur="$indicateurs['a_traiter']" icone="inbox" ton="attention" aide="Nouveaux + à vérifier" :href="route('responsable.signalements.index', ['statut' => 'a_verifier'])" />
        <x-cc.indicateur label="En intervention" :valeur="$indicateurs['en_intervention']" icone="truck" ton="lagon" aide="Affectés + en cours" :href="route('responsable.signalements.index', ['statut' => 'en_cours'])" />
        <x-cc.indicateur label="Résolutions à valider" :valeur="$indicateurs['a_valider']" icone="check-circle" ton="succes" :href="route('responsable.signalements.index', ['statut' => 'resolu'])" />
        <x-cc.indicateur label="Taux de résolution" :valeur="$indicateurs['taux_resolution'].' %'" icone="chart" aide="Sur les 6 derniers mois" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-5">
        <div class="space-y-6 xl:col-span-3">
            <x-cc.bloc titre="Dossiers urgents" description="Priorité élevée ou critique, non clôturés." flush>
                @if (collect($urgents)->isEmpty())
                    <p class="p-5 text-sm text-zinc-600">Aucun dossier urgent. 👏</p>
                @else
                    <ul class="divide-y divide-zinc-100">
                        @foreach ($urgents as $s)
                            <li>
                                <a href="{{ route('responsable.signalements.show', $s->id) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3 hover:bg-zinc-50 sm:px-5">
                                    <x-cc.priorite :priorite="$s->priorite" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-medium"><x-cc.categorie :categorie="$s->categorie" :avec-icone="false" /> — {{ $s->commune }}</span>
                                        <span class="block truncate text-sm text-zinc-600">{{ $s->reference }} · {{ $s->repere }}</span>
                                    </span>
                                    <x-cc.statut :statut="$s->statut" taille="sm" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-cc.bloc>

            <x-cc.bloc titre="Carte des dossiers ouverts" flush>
                <x-cc.carte class="rounded-none border-0" hauteur="h-80" :points="collect($carte)->map(fn ($s) => ['lat' => $s->latitude, 'lng' => $s->longitude, 'titre' => $s->reference.' — '.$s->commune, 'statut' => $s->statut, 'url' => route('responsable.signalements.show', $s->id)])" label="Carte des dossiers ouverts" />
                <ul class="flex flex-wrap gap-2 border-t border-zinc-100 p-3 text-xs" aria-label="Légende de la carte">
                    @foreach (['nouveau', 'a_verifier', 'valide', 'affecte', 'en_cours', 'resolu'] as $st)
                        <li><x-cc.statut :statut="$st" taille="sm" /></li>
                    @endforeach
                </ul>
            </x-cc.bloc>
        </div>

        <div class="space-y-6 xl:col-span-2">
            <x-cc.bloc titre="À vérifier" flush>
                <x-slot:actions><a href="{{ route('responsable.signalements.index', ['statut' => 'nouveau']) }}" class="cc-touch inline-flex items-center text-sm font-medium text-lagon-800 hover:underline">Tout voir</a></x-slot:actions>
                <ul class="divide-y divide-zinc-100">
                    @forelse ($nouveaux as $s)
                        <li><a href="{{ route('responsable.signalements.show', $s->id) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 sm:px-5">
                            <x-cc.icon :name="config('cleancomores.categories.'.$s->categorie.'.icone')" class="size-5 text-lagon-700" />
                            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $s->commune }} — {{ $s->repere }}</span><span class="text-xs text-zinc-600">{{ $s->created_at->diffForHumans() }}</span></span>
                            <x-cc.statut :statut="$s->statut" taille="sm" />
                        </a></li>
                    @empty
                        <li class="p-5 text-sm text-zinc-600">Rien à vérifier.</li>
                    @endforelse
                </ul>
            </x-cc.bloc>

            <x-cc.bloc titre="Résolutions à valider" flush>
                <ul class="divide-y divide-zinc-100">
                    @forelse ($a_valider as $s)
                        <li><a href="{{ route('responsable.signalements.show', $s->id) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-zinc-50 sm:px-5">
                            <x-cc.icon name="check-circle" class="size-5 text-teal-700" />
                            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium">{{ $s->reference }} — {{ $s->commune }}</span><span class="text-xs text-zinc-600">par {{ $s->agent?->name }} · {{ $s->updated_at->diffForHumans() }}</span></span>
                            <x-cc.icon name="chevron-right" class="size-4 text-zinc-400" />
                        </a></li>
                    @empty
                        <li class="p-5 text-sm text-zinc-600">Aucune résolution en attente.</li>
                    @endforelse
                </ul>
            </x-cc.bloc>
        </div>
    </div>
</x-layouts::espace>
