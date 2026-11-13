{{-- Souscription : adresse + GPS + téléphone de paiement. Variables : $formule, $utilisateur, $passerelle --}}
<x-layouts::espace role="citoyen" title="Souscrire — {{ $formule->nom }}">
    <x-cc.entete-page :titre="'Formule '.$formule->nom" :retour="route('citoyen.abonnements.index')" retour-label="Collecte à domicile"
        :sous-titre="number_format($formule->prix, 0, ',', ' ').' KMF · '.$formule->collectes_incluses.' collecte(s) · '.$formule->duree_jours.' jours'" />

    @if ($passerelle->estSimulation())
        <x-cc.alerte type="attention" titre="Mode démonstration : aucun argent réel" class="mb-6">
            Le paiement mobile n'est pas encore relié à un opérateur. Votre demande de paiement sera <strong>vérifiée et confirmée par un responsable</strong> CleanComores avant l'activation de l'abonnement.
        </x-cc.alerte>
    @endif

    <form method="POST" action="{{ route('citoyen.abonnements.store', $formule) }}" class="space-y-6 rounded-2xl border border-zinc-200 bg-white p-4 shadow-xs sm:p-6" novalidate>
        @csrf

        <fieldset class="space-y-5">
            <legend class="text-lg font-semibold">1. Adresse de collecte</legend>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-cc.champ label="Commune" pour="commune" requis>
                    <select id="commune" name="commune" class="cc-input" required @error('commune') aria-invalid="true" @enderror>
                        <option value="">Choisir…</option>
                        @foreach (config('cleancomores.zones') as $zone)
                            <optgroup label="{{ $zone['label'] }}">
                                @foreach ($zone['communes'] as $commune)
                                    <option value="{{ $commune }}" @selected(old('commune', $utilisateur->commune) === $commune)>{{ $commune }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </x-cc.champ>
                <x-cc.champ label="Adresse" pour="adresse" requis aide="Quartier, rue, numéro ou nom de la maison.">
                    <input id="adresse" name="adresse" type="text" maxlength="255" required class="cc-input" value="{{ old('adresse') }}" aria-describedby="adresse-aide" @error('adresse') aria-invalid="true" @enderror>
                </x-cc.champ>
            </div>
            <x-cc.champ label="Repère pour l'agent" pour="repere" aide="Ex. : portail bleu en face de la boulangerie.">
                <input id="repere" name="repere" type="text" maxlength="255" class="cc-input" value="{{ old('repere') }}" aria-describedby="repere-aide">
            </x-cc.champ>
        </fieldset>

        <fieldset class="space-y-4">
            <legend class="text-lg font-semibold">2. Position exacte du domicile</legend>
            <div class="rounded-xl bg-sky-50 p-4 ring-1 ring-sky-200">
                <p class="text-sm text-sky-950"><strong>Votre position n'est utilisée qu'avec votre accord</strong>, pour guider l'agent jusqu'à votre porte. Vous pouvez aussi toucher la carte.</p>
                <x-cc.bouton variante="secondaire" icone="location" data-geolocaliser="geo-statut" class="mt-3 max-sm:w-full">Utiliser ma position actuelle</x-cc.bouton>
                <p id="geo-statut" class="mt-2 text-sm font-medium data-[type=erreur]:text-red-800 data-[type=succes]:text-emerald-800" role="status" aria-live="polite"></p>
            </div>
            <x-cc.carte selection hauteur="h-72 sm:h-80" label="Carte : touchez pour placer votre domicile" />
            <input type="hidden" name="latitude" value="{{ old('latitude') }}">
            <input type="hidden" name="longitude" value="{{ old('longitude') }}">
            @error('latitude')<p class="flex items-center gap-1 text-sm font-medium text-red-700"><x-cc.icon name="alert" class="size-4" />{{ $message }}</p>@enderror
        </fieldset>

        <fieldset class="space-y-5">
            <legend class="text-lg font-semibold">3. Paiement mobile</legend>
            <x-cc.champ label="Numéro de téléphone pour le paiement" pour="telephone" requis aide="Numéro comorien, ex. : 321 00 11. L'agent pourra aussi vous appeler avant son passage.">
                <x-cc.telephone requis :value="old('telephone', \App\Support\Telephone::afficher($utilisateur->telephone))" />
            </x-cc.champ>
            <div class="rounded-xl bg-sable-100 p-4 text-sm">
                <p class="flex justify-between gap-4"><span>Formule {{ $formule->nom }}</span><strong class="tabular-nums">{{ number_format($formule->prix, 0, ',', ' ') }} KMF</strong></p>
                <p class="mt-1 text-zinc-600">Moyen de paiement : {{ config('cleancomores.paiement.operateurs.'.$passerelle->code()) }}</p>
            </div>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" name="conditions" value="1" required class="mt-0.5 size-5 rounded accent-lagon-700" @checked(old('conditions'))>
                <span>J'ai compris que l'abonnement ne sera actif <strong>qu'après confirmation du paiement</strong>, et que chaque passage consomme une collecte de ma formule.</span>
            </label>
            @error('conditions')<p class="text-sm font-medium text-red-700">{{ $message }}</p>@enderror
        </fieldset>

        <div class="flex justify-end">
            <x-cc.bouton type="submit" icone="send" class="max-sm:w-full">Demander le paiement</x-cc.bouton>
        </div>
    </form>
</x-layouts::espace>
