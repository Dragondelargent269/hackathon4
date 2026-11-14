<x-layouts::public>
    <x-slot:pied>
        <footer class="border-t border-zinc-200 bg-white px-4 py-8 text-sm text-zinc-600">
            <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4">
                <x-cc.logo />
                <p>Plateforme citoyenne de signalement des déchets — Ngazidja · Ndzuani · Mwali</p>
            </div>
        </footer>
    </x-slot:pied>

    {{-- Héros --}}
    <section class="mx-auto grid max-w-6xl items-center gap-10 py-6 lg:grid-cols-2 lg:py-14">
        <div>
            <p class="inline-flex items-center gap-2 rounded-full bg-lagon-50 px-3 py-1 text-sm font-medium text-lagon-800 ring-1 ring-lagon-200">
                <x-cc.icon name="leaf" class="size-4" /> Pour des Comores plus propres
            </p>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl">
                Signalez un déchet.<br><span class="text-lagon-700">Suivez son ramassage.</span>
            </h1>
            <p class="mt-4 max-w-xl text-lg text-zinc-700">
                Dépôt sauvage, poubelle qui déborde, plastiques sur la plage : en trois étapes, prévenez les équipes
                municipales et suivez chaque étape jusqu'au nettoyage.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <x-cc.bouton :href="route('inscription')" icone="plus" class="px-6 text-base">Faire un signalement</x-cc.bouton>
                <x-cc.bouton :href="route('connexion')" variante="secondaire" class="px-6 text-base">J'ai déjà un compte</x-cc.bouton>
            </div>
        </div>

        {{-- Illustration : aperçu d'un suivi --}}
        <div class="relative">
            <div class="absolute -inset-4 -z-10 rounded-[2rem] bg-gradient-to-br from-lagon-100 via-sable-100 to-sky-100" aria-hidden="true"></div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-lg" aria-hidden="true">
                <div class="flex items-center justify-between">
                    <x-cc.categorie categorie="poubelle_debordante" class="font-semibold" />
                    <x-cc.statut statut="en_cours" taille="sm" />
                </div>
                <p class="mt-2 text-sm text-zinc-600">Place de l'Indépendance, Moroni</p>
                <x-cc.progression statut="en_cours" class="mt-4" />
                <ul class="mt-5 space-y-3 text-sm">
                    <li class="flex gap-3"><span class="mt-1 size-2.5 rounded-full bg-orange-500"></span><span><strong>En cours</strong> — L'équipe est sur place · 10:42</span></li>
                    <li class="flex gap-3"><span class="mt-1 size-2.5 rounded-full bg-violet-600"></span><span><strong>Affecté</strong> — Équipe de Moroni · 08:15</span></li>
                    <li class="flex gap-3"><span class="mt-1 size-2.5 rounded-full bg-sky-600"></span><span><strong>Validé</strong> — Priorité élevée · hier</span></li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Comment ça marche --}}
    <section class="mx-auto max-w-6xl py-10" aria-labelledby="etapes-titre">
        <h2 id="etapes-titre" class="text-2xl font-semibold text-zinc-900">Comment ça marche ?</h2>
        <ol class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['pencil', 'Décrivez', 'Choisissez le type de problème, ajoutez une description et, si vous le souhaitez, une photo.'],
                ['map-pin', 'Localisez', 'Partagez votre position (avec votre accord) ou placez un repère sur la carte.'],
                ['bell', 'Suivez', 'Chaque étape est horodatée : vérification, intervention, nettoyage, validation.'],
            ] as $i => [$icone, $titre, $texte])
                <li class="rounded-xl border border-zinc-200 bg-white p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-lg bg-lagon-700 text-white"><x-cc.icon :name="$icone" /></span>
                        <span class="text-sm font-semibold text-lagon-800">Étape {{ $i + 1 }}</span>
                    </div>
                    <h3 class="mt-3 text-lg font-semibold">{{ $titre }}</h3>
                    <p class="mt-1 text-zinc-700">{{ $texte }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Ce qu'on peut signaler --}}
    <section class="mx-auto max-w-6xl py-10" aria-labelledby="cat-titre">
        <h2 id="cat-titre" class="text-2xl font-semibold text-zinc-900">Ce que vous pouvez signaler</h2>
        <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (config('cleancomores.categories') as $cle => $c)
                <li class="rounded-xl bg-white p-5 ring-1 ring-zinc-200">
                    <x-cc.icon :name="$c['icone']" class="size-7 text-lagon-700" />
                    <h3 class="mt-3 font-semibold">{{ $c['label'] }}</h3>
                    <p class="mt-1 text-sm text-zinc-600">{{ $c['aide'] }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Confidentialité --}}
    <section class="mx-auto max-w-6xl pb-12">
        <div class="flex flex-col gap-4 rounded-2xl bg-lagon-900 p-6 text-lagon-50 sm:flex-row sm:items-center sm:p-8">
            <x-cc.icon name="shield" class="size-10 shrink-0 text-lagon-200" />
            <div class="flex-1">
                <h2 class="text-lg font-semibold text-white">Vos données sont protégées</h2>
                <p class="mt-1 text-lagon-100">Votre nom et vos coordonnées ne sont visibles que par les équipes municipales autorisées. Votre position n'est utilisée qu'avec votre accord.</p>
            </div>
            <x-cc.bouton :href="route('inscription')" variante="secondaire">Créer mon compte</x-cc.bouton>
        </div>
    </section>
</x-layouts::public>
