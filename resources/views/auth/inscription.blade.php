{{-- Inscription citoyen uniquement (les rôles Responsable / Agent sont attribués par l'organisation).
     POST vers la route Fortify « register ». Champs : name, email|telephone (au moins un), commune, password, password_confirmation, conditions --}}
<x-layouts::public title="Créer un compte" centre>
    <div class="w-full max-w-lg">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold tracking-tight">Créer un compte citoyen</h1>
            <p class="mt-1 text-zinc-600">Pour signaler un problème de propreté et suivre sa prise en charge.</p>

            <x-cc.flash class="mt-4" />

            <form method="POST" action="{{ Route::has('register') ? route('register') : '#' }}" class="mt-6 space-y-5">
                @csrf
                <x-cc.champ label="Nom complet" pour="name" requis>
                    <input id="name" name="name" type="text" class="cc-input" required autocomplete="name" value="{{ old('name') }}"
                           @error('name') aria-invalid="true" aria-describedby="name-erreur" @enderror>
                </x-cc.champ>

                <fieldset class="space-y-4 rounded-xl border border-zinc-200 p-4">
                    <legend class="px-1 text-sm font-medium text-zinc-900">Comment vous joindre ? <span class="font-normal text-zinc-600">— au moins l'un des deux</span></legend>
                    <x-cc.champ label="Numéro de téléphone" pour="telephone" aide="7 chiffres commençant par 3, 4 ou 7, avec ou sans +269. Ex. : 321 00 11.">
                        <x-cc.telephone :value="old('telephone')" />
                    </x-cc.champ>
                    <x-cc.champ label="Adresse e-mail" pour="email" aide="Nécessaire pour recevoir les notifications par e-mail.">
                        <input id="email" name="email" type="email" class="cc-input" autocomplete="email" value="{{ old('email') }}"
                               aria-describedby="email-aide @error('email') email-erreur @enderror" @error('email') aria-invalid="true" @enderror>
                    </x-cc.champ>
                </fieldset>

                <x-cc.champ label="Commune" pour="commune" requis>
                    <select id="commune" name="commune" class="cc-input" required @error('commune') aria-invalid="true" aria-describedby="commune-erreur" @enderror>
                        <option value="">Choisir votre commune…</option>
                        @foreach (config('cleancomores.zones') as $zone)
                            <optgroup label="{{ $zone['label'] }}">
                                @foreach ($zone['communes'] as $commune)
                                    <option value="{{ $commune }}" @selected(old('commune') === $commune)>{{ $commune }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </x-cc.champ>

                <div class="grid gap-5 sm:grid-cols-2">
                    <x-cc.champ label="Mot de passe" pour="password" requis aide="8 caractères minimum.">
                        <input id="password" name="password" type="password" class="cc-input" required minlength="8" autocomplete="new-password" aria-describedby="password-aide"
                               @error('password') aria-invalid="true" @enderror>
                    </x-cc.champ>
                    <x-cc.champ label="Confirmer le mot de passe" pour="password_confirmation" requis>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="cc-input" required minlength="8" autocomplete="new-password">
                    </x-cc.champ>
                </div>

                <label class="flex gap-3 text-sm text-zinc-700">
                    <input type="checkbox" name="conditions" value="1" required class="mt-0.5 size-5 shrink-0 rounded border-zinc-400" @checked(old('conditions'))>
                    <span>J'accepte que mes coordonnées soient utilisées uniquement par les équipes municipales pour traiter mes signalements.</span>
                </label>

                <x-cc.bouton type="submit" pleine>Créer mon compte</x-cc.bouton>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-zinc-700">
            Déjà inscrit ? <a href="{{ route('connexion') }}" class="font-semibold text-lagon-800 underline underline-offset-2">Se connecter</a>
        </p>
    </div>
</x-layouts::public>
