{{-- Profil citoyen. Variable : $utilisateur (ou auth()->user())
     Formulaires : citoyen.profil.update (PUT), citoyen.profil.notifications (PUT), citoyen.profil.mot-de-passe (PUT) --}}
@php $u = $utilisateur ?? auth()->user(); @endphp
<x-layouts::espace role="citoyen" title="Mon profil">
    <x-cc.entete-page titre="Mon profil" sous-titre="Vos informations ne sont visibles que par les équipes municipales autorisées." />

    <div class="space-y-6">
        <x-cc.bloc titre="Informations personnelles">
            <form method="POST" action="{{ route('citoyen.profil.update') }}" class="grid gap-5 sm:grid-cols-2">
                @csrf @method('PUT')
                <x-cc.champ label="Nom complet" pour="name" requis class="sm:col-span-2">
                    <input id="name" name="name" class="cc-input" required autocomplete="name" value="{{ old('name', $u->name) }}">
                </x-cc.champ>
                <x-cc.champ label="Téléphone" pour="telephone" aide="7 chiffres commençant par 3, 4 ou 7, avec ou sans +269. Ex. : 321 00 11.">
                    <x-cc.telephone :value="old('telephone', \App\Support\Telephone::afficher($u->telephone))" />
                </x-cc.champ>
                <x-cc.champ label="E-mail" pour="email">
                    <input id="email" name="email" type="email" class="cc-input" autocomplete="email" value="{{ old('email', $u->email) }}">
                </x-cc.champ>
                <x-cc.champ label="Commune" pour="commune" requis>
                    <select id="commune" name="commune" class="cc-input" required>
                        @foreach (config('cleancomores.zones') as $zone)
                            <optgroup label="{{ $zone['label'] }}">
                                @foreach ($zone['communes'] as $commune)
                                    <option @selected(old('commune', $u->commune) === $commune)>{{ $commune }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </x-cc.champ>
                <div class="flex items-end sm:justify-end"><x-cc.bouton type="submit" class="max-sm:w-full">Enregistrer</x-cc.bouton></div>
            </form>
        </x-cc.bloc>

        <x-cc.bloc titre="Notifications par e-mail" description="Choisissez quand être prévenu(e).">
            @if (empty($u->email))
                <x-cc.alerte type="attention" class="mb-4">Ajoutez une adresse e-mail ci-dessus pour recevoir des notifications.</x-cc.alerte>
            @endif
            <form method="POST" action="{{ route('citoyen.profil.notifications') }}" class="space-y-1">
                @csrf @method('PUT')
                @foreach ([
                    ['notif_email', 'Recevoir des e-mails de CleanComores', 'Interrupteur général.'],
                    ['notif_changement_statut', 'À chaque changement de statut', 'Validé, affecté, en cours…'],
                    ['notif_resolution', 'Quand le problème est résolu ou rejeté', 'Recommandé.'],
                ] as [$nom, $label, $aide])
                    <label class="flex min-h-14 cursor-pointer items-center justify-between gap-4 rounded-lg px-1 py-2 hover:bg-zinc-50">
                        <span><span class="block font-medium">{{ $label }}</span><span class="block text-sm text-zinc-600">{{ $aide }}</span></span>
                        <span class="relative inline-flex shrink-0">
                            <input type="hidden" name="{{ $nom }}" value="0">
                            <input type="checkbox" name="{{ $nom }}" value="1" role="switch" class="peer sr-only" @checked(old($nom, $u->$nom ?? false)) @disabled(empty($u->email))>
                            <span class="h-7 w-12 rounded-full bg-zinc-300 transition-colors peer-checked:bg-lagon-700 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-lagon-600 peer-disabled:opacity-50"></span>
                            <span class="absolute start-1 top-1 size-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
                        </span>
                    </label>
                @endforeach
                <div class="pt-3 sm:text-end"><x-cc.bouton type="submit" variante="secondaire" class="max-sm:w-full">Enregistrer mes préférences</x-cc.bouton></div>
            </form>
        </x-cc.bloc>

        <x-cc.bloc titre="Mot de passe">
            <form method="POST" action="{{ route('citoyen.profil.mot-de-passe') }}" class="grid gap-5 sm:grid-cols-3">
                @csrf @method('PUT')
                <x-cc.champ label="Mot de passe actuel" pour="current_password" requis>
                    <input id="current_password" name="current_password" type="password" class="cc-input" required autocomplete="current-password">
                </x-cc.champ>
                <x-cc.champ label="Nouveau mot de passe" pour="password" requis>
                    <input id="password" name="password" type="password" class="cc-input" required minlength="8" autocomplete="new-password">
                </x-cc.champ>
                <x-cc.champ label="Confirmation" pour="password_confirmation" requis>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="cc-input" required minlength="8" autocomplete="new-password">
                </x-cc.champ>
                <div class="sm:col-span-3 sm:text-end"><x-cc.bouton type="submit" variante="secondaire" class="max-sm:w-full">Changer le mot de passe</x-cc.bouton></div>
            </form>
        </x-cc.bloc>
    </div>
</x-layouts::espace>
