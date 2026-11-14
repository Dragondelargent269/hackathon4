{{--
    Création d'un signalement en 3 étapes : Décrire → Localiser → Confirmer.
    POST multipart vers citoyen.signalements.store
    Champs : categorie*, description*, photo (image ≤ 5 Mo), commune*, repere, latitude, longitude, consentement_position
    Sans JavaScript, les trois étapes s'affichent les unes sous les autres.
--}}
<x-layouts::espace role="citoyen" title="Signaler un problème">
    <x-cc.entete-page titre="Signaler un problème" :retour="route('citoyen.signalements.index')" retour-label="Mes signalements" />

    <form method="POST" action="{{ route('citoyen.signalements.store') }}" enctype="multipart/form-data" data-etapes class="scroll-mt-20" novalidate>
        @csrf

        {{-- Indicateur d'étapes --}}
        <ol class="mb-6 grid grid-cols-3 gap-2" aria-label="Étapes du signalement">
            @foreach (['Décrire', 'Localiser', 'Confirmer'] as $i => $nom)
                <li data-indicateur-etape="{{ $i + 1 }}" class="group">
                    <span class="block h-1.5 rounded-full bg-zinc-200 group-data-[etat=courant]:bg-lagon-600 group-data-[etat=fait]:bg-lagon-600"></span>
                    <span class="mt-2 flex items-center gap-1.5 text-sm font-medium text-zinc-500 group-data-[etat=courant]:text-zinc-900 group-data-[etat=fait]:text-lagon-800">
                        <span class="grid size-6 place-items-center rounded-full bg-zinc-200 text-xs group-data-[etat=courant]:bg-lagon-700 group-data-[etat=courant]:text-white group-data-[etat=fait]:bg-lagon-100">{{ $i + 1 }}</span>
                        {{ $nom }}
                    </span>
                </li>
            @endforeach
        </ol>

        <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs sm:p-6">
            {{-- ÉTAPE 1 : DÉCRIRE --}}
            <fieldset data-etape="1" class="space-y-6">
                <legend class="text-xl font-semibold outline-none">1. Décrivez le problème</legend>

                <div class="space-y-2">
                    <p id="categorie-label" class="text-sm font-medium">Type de problème <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only">(obligatoire)</span></p>
                    <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-labelledby="categorie-label">
                        @foreach (config('cleancomores.categories') as $cle => $c)
                            <label class="flex min-h-16 cursor-pointer items-start gap-3 rounded-xl border border-zinc-300 p-3 has-checked:border-lagon-700 has-checked:bg-lagon-50 has-checked:ring-1 has-checked:ring-lagon-700 has-focus-visible:outline-2 has-focus-visible:outline-lagon-600">
                                <input type="radio" name="categorie" value="{{ $cle }}" data-label="{{ $c['label'] }}" required class="sr-only" @checked(old('categorie') === $cle)>
                                <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-lagon-700 ring-1 ring-zinc-200"><x-cc.icon :name="$c['icone']" class="size-6" /></span>
                                <span><span class="block font-semibold">{{ $c['label'] }}</span><span class="block text-sm text-zinc-600">{{ $c['aide'] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('categorie')<p class="text-sm font-medium text-red-700">{{ $message }}</p>@enderror
                </div>

                <x-cc.champ label="Description" pour="description" requis aide="Que voyez-vous ? Depuis quand ? Y a-t-il un danger (odeur, eau stagnante, enfants à proximité) ?">
                    <textarea id="description" name="description" rows="4" class="cc-input" required minlength="10" maxlength="1000" aria-describedby="description-aide"
                              @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                </x-cc.champ>

                <div class="space-y-2">
                    <x-cc.champ label="Photo" pour="photo" aide="JPG ou PNG, 5 Mo maximum. Une photo aide les équipes à préparer l'intervention.">
                        <label for="photo" class="flex min-h-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-zinc-300 bg-sable-50 p-4 text-center text-sm text-zinc-700 hover:border-lagon-600">
                            <x-cc.icon name="camera" class="size-7 text-lagon-700" />
                            <span class="font-medium">Prendre ou choisir une photo</span>
                        </label>
                        <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="sr-only" data-apercu="photo-apercu" aria-describedby="photo-aide">
                    </x-cc.champ>
                    <div id="photo-apercu" hidden class="flex items-center gap-3 rounded-xl border border-zinc-200 p-2">
                        <img alt="Aperçu de la photo choisie" class="size-20 rounded-lg object-cover">
                        <span class="min-w-0 flex-1 truncate text-sm" data-nom-fichier></span>
                        <x-cc.bouton variante="fantome" data-retirer-photo="photo" icone="x">Retirer</x-cc.bouton>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-cc.bouton data-suivant icone="arrow-right" class="flex-row-reverse max-sm:w-full">Continuer</x-cc.bouton>
                </div>
            </fieldset>

            {{-- ÉTAPE 2 : LOCALISER --}}
            <fieldset data-etape="2" class="mt-10 space-y-6 [.js_&]:mt-0">
                <legend class="text-xl font-semibold outline-none">2. Où se trouve le problème ?</legend>

                <div class="rounded-xl bg-sky-50 p-4 ring-1 ring-sky-200">
                    <p class="text-sm text-sky-950">
                        <strong>Votre position n'est utilisée qu'avec votre accord</strong>, uniquement pour situer ce signalement.
                        Vous pouvez aussi toucher la carte pour placer le repère vous-même.
                    </p>
                    <x-cc.bouton variante="secondaire" icone="location" data-geolocaliser="geo-statut" class="mt-3 max-sm:w-full">Utiliser ma position actuelle</x-cc.bouton>
                    <p id="geo-statut" class="mt-2 text-sm font-medium data-[type=erreur]:text-red-800 data-[type=succes]:text-emerald-800" role="status" aria-live="polite"></p>
                </div>

                <x-cc.carte selection hauteur="h-72 sm:h-80" label="Carte : touchez pour placer le repère du signalement" />
                <input type="hidden" name="latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" value="{{ old('longitude') }}">

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-cc.champ label="Commune" pour="commune" requis>
                        <select id="commune" name="commune" class="cc-input" required @error('commune') aria-invalid="true" @enderror>
                            <option value="">Choisir…</option>
                            @foreach (config('cleancomores.zones') as $zone)
                                <optgroup label="{{ $zone['label'] }}">
                                    @foreach ($zone['communes'] as $commune)
                                        <option value="{{ $commune }}" @selected(old('commune', $utilisateur->commune ?? auth()->user()?->commune) === $commune)>{{ $commune }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </x-cc.champ>
                    <x-cc.champ label="Repère / adresse" pour="repere" aide="Ex. : derrière le marché, près de la mosquée…">
                        <input id="repere" name="repere" type="text" maxlength="255" class="cc-input" value="{{ old('repere') }}" aria-describedby="repere-aide">
                    </x-cc.champ>
                </div>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <x-cc.bouton variante="secondaire" data-precedent icone="arrow-left">Retour</x-cc.bouton>
                    <x-cc.bouton data-suivant icone="arrow-right" class="flex-row-reverse">Continuer</x-cc.bouton>
                </div>
            </fieldset>

            {{-- ÉTAPE 3 : CONFIRMER --}}
            <fieldset data-etape="recap" class="mt-10 space-y-6 [.js_&]:mt-0">
                <legend class="text-xl font-semibold outline-none">3. Vérifiez et envoyez</legend>

                <dl class="divide-y divide-zinc-100 rounded-xl border border-zinc-200">
                    @foreach ([
                        ['Type de problème', 'categorie', 1],
                        ['Description', 'description', 1],
                        ['Photo', 'photo', 1],
                        ['Commune', 'commune', 2],
                        ['Repère', 'repere', 2],
                        ['Coordonnées GPS', 'latitude,longitude', 2],
                    ] as [$label, $champs, $etape])
                        <div class="flex items-start gap-3 p-3 sm:p-4">
                            <dt class="w-32 shrink-0 text-sm text-zinc-600 sm:w-40">{{ $label }}</dt>
                            <dd class="min-w-0 flex-1 break-words text-sm font-medium" data-recap="{{ $champs }}" data-vide="Non renseigné">—</dd>
                            <button type="button" data-aller-etape="{{ $etape - 1 }}" class="cc-touch -my-2 shrink-0 rounded-lg px-2 text-sm font-medium text-lagon-800 underline-offset-2 hover:underline">
                                Modifier<span class="sr-only"> : {{ $label }}</span>
                            </button>
                        </div>
                    @endforeach
                </dl>

                <x-cc.alerte type="info">
                    Après l'envoi, votre signalement passe au statut <strong>Nouveau</strong>. Vous suivrez chaque étape dans « Mes signalements »
                    et recevrez un e-mail si vous l'avez activé dans votre profil.
                </x-cc.alerte>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
                    <x-cc.bouton variante="secondaire" data-precedent icone="arrow-left">Retour</x-cc.bouton>
                    <x-cc.bouton type="submit" icone="send">Envoyer le signalement</x-cc.bouton>
                </div>
            </fieldset>
        </div>
    </form>
</x-layouts::espace>
