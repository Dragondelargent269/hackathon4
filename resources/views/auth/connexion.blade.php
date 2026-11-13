{{-- POST vers la route Fortify « login ». Champ identifiant = e-mail OU téléphone (à gérer côté back). --}}
<x-layouts::public title="Connexion" centre>
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold tracking-tight">Connexion</h1>
            <p class="mt-1 text-zinc-600">Citoyens, responsables et agents utilisent le même accès.</p>

            <x-cc.flash class="mt-4" />
            @if (session('status'))<x-cc.alerte type="succes" class="mt-4">{{ session('status') }}</x-cc.alerte>@endif

            <form method="POST" action="{{ Route::has('login') ? route('login') : '#' }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <x-cc.champ label="E-mail ou numéro de téléphone" pour="identifiant" requis>
                    <input id="identifiant" name="identifiant" type="text" class="cc-input" required autofocus
                           autocomplete="username" inputmode="email" value="{{ old('identifiant') }}"
                           placeholder="ex. nom@exemple.km ou +269 3xx xx xx"
                           @error('identifiant') aria-invalid="true" aria-describedby="identifiant-erreur" @enderror>
                </x-cc.champ>

                <x-cc.champ label="Mot de passe" pour="password" requis>
                    <input id="password" name="password" type="password" class="cc-input" required autocomplete="current-password"
                           @error('password') aria-invalid="true" aria-describedby="password-erreur" @enderror>
                </x-cc.champ>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <label class="flex min-h-11 items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" class="size-5 rounded border-zinc-400 text-lagon-700"> Rester connecté
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="cc-touch inline-flex items-center text-sm font-medium text-lagon-800 underline-offset-2 hover:underline">Mot de passe oublié ?</a>
                    @endif
                </div>

                <x-cc.bouton type="submit" pleine>Se connecter</x-cc.bouton>
            </form>
        </div>
        <p class="mt-6 text-center text-sm text-zinc-700">
            Pas encore de compte ?
            <a href="{{ route('inscription') }}" class="font-semibold text-lagon-800 underline underline-offset-2">Créer un compte citoyen</a>
        </p>
    </div>
</x-layouts::public>
