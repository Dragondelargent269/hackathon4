{{--
    Dossier côté responsable : vérifier, prioriser, affecter, rejeter, valider la résolution ou demander une reprise.
    Variables : $signalement, $agents (itérable { id, name, zone, missions_en_cours, disponible })
    Les actions affichées dépendent du statut. Rejet, affectation et clôture passent par une confirmation.
--}}
@php
    $s = $signalement;
    $ouvert = ! in_array($s->statut, ['verifie', 'rejete']);
@endphp
<x-layouts::espace role="responsable" :title="'Dossier '.$s->reference" large>
    <x-cc.entete-page :titre="config('cleancomores.categories.'.$s->categorie.'.label').' — '.$s->commune" :retour="route('responsable.signalements.index')" retour-label="Tous les signalements">
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <span class="font-mono text-sm text-zinc-600">{{ $s->reference }}</span>
            <x-cc.statut :statut="$s->statut" />
            <x-cc.priorite :priorite="$s->priorite" />
        </div>
    </x-cc.entete-page>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Colonne principale --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- ===== Panneau d'action selon le statut ===== --}}
            @if ($s->statut === 'nouveau')
                <x-cc.bloc titre="Prochaine étape : vérification" class="border-amber-300 ring-1 ring-amber-200">
                    <p class="text-sm text-zinc-700">Ce signalement n'a pas encore été examiné. Ouvrez la vérification pour le valider ou le rejeter.</p>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <form method="POST" action="{{ route('responsable.signalements.examiner', $s->id) }}">@csrf
                            <x-cc.bouton type="submit" icone="search" class="max-sm:w-full">Commencer la vérification</x-cc.bouton>
                        </form>
                        <x-cc.bouton variante="secondaire" icone="x-circle" data-ouvrir="dialogue-rejet">Rejeter</x-cc.bouton>
                    </div>
                </x-cc.bloc>
            @elseif ($s->statut === 'a_verifier')
                <x-cc.bloc titre="Vérifier le signalement" description="Confirmez que le problème est réel et définissez sa priorité." class="border-amber-300 ring-1 ring-amber-200">
                    <form method="POST" action="{{ route('responsable.signalements.valider', $s->id) }}" class="space-y-4">
                        @csrf
                        <fieldset>
                            <legend class="text-sm font-medium">Priorité <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only">(obligatoire)</span></legend>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach (config('cleancomores.priorites') as $cle => $p)
                                    <label class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-zinc-300 px-3 text-sm font-medium has-checked:border-lagon-700 has-checked:bg-lagon-50 has-checked:ring-1 has-checked:ring-lagon-700 has-focus-visible:outline-2 has-focus-visible:outline-lagon-600">
                                        <input type="radio" name="priorite" value="{{ $cle }}" required class="sr-only" @checked(old('priorite') === $cle)>
                                        <x-cc.priorite :priorite="$cle" />
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <x-cc.bouton type="submit" icone="check" class="max-sm:w-full">Valider le signalement</x-cc.bouton>
                            <x-cc.bouton variante="secondaire" icone="x-circle" data-ouvrir="dialogue-rejet">Rejeter…</x-cc.bouton>
                        </div>
                    </form>
                </x-cc.bloc>
            @elseif (in_array($s->statut, ['valide', 'affecte']))
                <x-cc.bloc :titre="$s->statut === 'valide' ? 'Affecter à un agent' : 'Réaffecter la mission'" description="Choisissez un agent disponible et précisez vos consignes.">
                    @if (collect($agents)->isEmpty())
                        {{-- Aucun compte avec le rôle « agent » : l'inscription publique crée uniquement des citoyens --}}
                        <x-cc.alerte type="attention" titre="Aucun agent de terrain enregistré">
                            Aucun compte n'a le rôle « Agent de terrain ». Les inscriptions du site créent uniquement des comptes citoyens :
                            les agents sont créés par un responsable dans l'administration.
                        </x-cc.alerte>
                        <x-cc.bouton class="mt-4" :href="url('/admin/users')" icone="users">Créer ou nommer un agent</x-cc.bouton>
                    @else
                    @if (collect($agents)->every(fn ($a) => ! $a->disponible))
                        <x-cc.alerte type="attention" class="mb-4">Tous les agents ont déjà 3 missions en cours. Attendez qu'une mission se termine ou nommez un autre agent dans l'<a href="{{ url('/admin/users') }}" class="font-medium underline">administration</a>.</x-cc.alerte>
                    @endif
                    <form id="form-affecter" method="POST" action="{{ route('responsable.signalements.affecter', $s->id) }}" data-confirmer="dialogue-affecter" class="space-y-4">
                        @csrf
                        <x-cc.champ label="Agent de terrain" pour="agent_id" requis>
                            <select id="agent_id" name="agent_id" class="cc-input" required>
                                <option value="">Choisir un agent…</option>
                                @foreach ($agents as $a)
                                    <option value="{{ $a->id }}" @disabled(! $a->disponible) @selected(old('agent_id', $s->agent?->id) == $a->id)>
                                        {{ $a->name }} — {{ ($a->zone ? config('cleancomores.zones.'.$a->zone.'.label') : null) ?? $a->commune ?? 'zone non renseignée' }} ·{{ $a->missions_en_cours }} mission(s){{ $a->disponible ? '' : ' · indisponible' }}
                                    </option>
                                @endforeach
                            </select>
                        </x-cc.champ>
                        <x-cc.champ label="Consignes pour l'agent" pour="consignes" aide="Matériel, accès, précautions, délai souhaité…">
                            <textarea id="consignes" name="consignes" rows="3" class="cc-input" maxlength="1000" aria-describedby="consignes-aide">{{ old('consignes', $s->consignes) }}</textarea>
                        </x-cc.champ>
                        <x-cc.bouton type="submit" icone="user" class="max-sm:w-full">{{ $s->statut === 'valide' ? 'Affecter la mission' : 'Réaffecter' }}</x-cc.bouton>
                    </form>
                    @endif
                </x-cc.bloc>
            @elseif ($s->statut === 'resolu')
                <x-cc.bloc titre="Valider la résolution" description="L'agent a transmis la fin d'intervention. Contrôlez le compte rendu." class="border-teal-300 ring-1 ring-teal-200">
                    @if ($s->compte_rendu)
                        <div class="rounded-lg bg-sable-100 p-4">
                            <p class="text-sm font-semibold">Compte rendu de {{ $s->agent?->name }} <span class="font-normal text-zinc-600">· {{ $s->compte_rendu->created_at->translatedFormat('d M Y à H:i') }}</span></p>
                            <p class="mt-1 text-sm">{{ $s->compte_rendu->texte }}</p>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><p class="mb-1 text-xs font-medium text-zinc-600">Avant (photo du citoyen)</p><x-cc.photo :url="$s->photo_url" /></div>
                            <div><p class="mb-1 text-xs font-medium text-zinc-600">Après (photo de preuve)</p><x-cc.photo :url="$s->compte_rendu->photo_url" alt="Photo de preuve après intervention" /></div>
                        </div>
                    @endif
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        <x-cc.bouton variante="succes" icone="shield" data-ouvrir="dialogue-cloturer">Valider et clôturer</x-cc.bouton>
                        <x-cc.bouton variante="secondaire" icone="refresh" data-ouvrir="dialogue-reprise">Demander une reprise</x-cc.bouton>
                    </div>
                </x-cc.bloc>
            @elseif ($s->statut === 'en_cours')
                <x-cc.alerte type="info" titre="Intervention en cours">{{ $s->agent?->name }} est sur le terrain. Vous serez notifié(e) à la fin de l'intervention.</x-cc.alerte>
            @elseif ($s->statut === 'verifie')
                <x-cc.alerte type="succes" titre="Dossier clôturé">La résolution a été validée. Aucune action n'est nécessaire.</x-cc.alerte>
            @elseif ($s->statut === 'rejete')
                <x-cc.alerte type="erreur" titre="Signalement rejeté">{{ $s->motif_rejet }}</x-cc.alerte>
            @endif

            {{-- ===== Détails ===== --}}
            <x-cc.bloc titre="Détails du signalement">
                <div class="grid gap-5 sm:grid-cols-2">
                    <dl class="space-y-3 text-sm">
                        <div><dt class="text-zinc-600">Catégorie</dt><dd class="font-medium"><x-cc.categorie :categorie="$s->categorie" /></dd></div>
                        <div><dt class="text-zinc-600">Description</dt><dd class="whitespace-pre-line">{{ $s->description }}</dd></div>
                        <div><dt class="text-zinc-600">Lieu</dt><dd class="font-medium">{{ $s->commune }} ({{ config('cleancomores.zones.'.$s->zone.'.label') }})</dd><dd>{{ $s->repere }}</dd></div>
                        <div><dt class="text-zinc-600">Reçu le</dt><dd>{{ $s->created_at->translatedFormat('d M Y à H:i') }}</dd></div>
                    </dl>
                    <x-cc.photo :url="$s->photo_url" />
                </div>
            </x-cc.bloc>

            @if ($s->latitude && $s->longitude)
                <x-cc.carte :points="[['lat' => $s->latitude, 'lng' => $s->longitude, 'titre' => $s->reference, 'statut' => $s->statut]]" hauteur="h-64" label="Emplacement du signalement">
                    <p class="font-mono text-xs">{{ $s->latitude }}, {{ $s->longitude }}</p>
                </x-cc.carte>
            @endif
        </div>

        {{-- Colonne latérale --}}
        <div class="space-y-6">
            <x-cc.bloc titre="Citoyen" description="Données personnelles — usage interne uniquement.">
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center gap-2"><x-cc.icon name="user" class="size-4 text-zinc-500" /><dt class="sr-only">Nom</dt><dd class="font-medium">{{ $s->citoyen->name }}</dd></div>
                    @if ($s->citoyen->telephone)
                        <div class="flex items-center gap-2"><x-cc.icon name="phone" class="size-4 text-zinc-500" /><dt class="sr-only">Téléphone</dt><dd><a class="underline-offset-2 hover:underline" href="tel:{{ preg_replace('/\s+/', '', $s->citoyen->telephone) }}">{{ $s->citoyen->telephone }}</a></dd></div>
                    @endif
                    @if ($s->citoyen->email)
                        <div class="flex items-center gap-2"><x-cc.icon name="mail" class="size-4 text-zinc-500" /><dt class="sr-only">E-mail</dt><dd class="truncate"><a class="underline-offset-2 hover:underline" href="mailto:{{ $s->citoyen->email }}">{{ $s->citoyen->email }}</a></dd></div>
                    @endif
                </dl>
            </x-cc.bloc>

            @if ($s->agent)
                <x-cc.bloc titre="Affectation">
                    <p class="flex items-center gap-2 text-sm font-medium"><x-cc.icon name="truck" class="size-4 text-zinc-500" />{{ $s->agent->name }}</p>
                    @if ($s->consignes)<p class="mt-2 rounded-lg bg-sable-100 p-3 text-sm">{{ $s->consignes }}</p>@endif
                </x-cc.bloc>
            @endif

            @if ($ouvert && $s->priorite)
                <x-cc.bloc titre="Modifier la priorité">
                    <form method="POST" action="{{ route('responsable.signalements.priorite', $s->id) }}" class="flex gap-2">
                        @csrf
                        <label for="priorite-modif" class="sr-only">Priorité</label>
                        <select id="priorite-modif" name="priorite" class="cc-input">
                            @foreach (config('cleancomores.priorites') as $cle => $p)
                                <option value="{{ $cle }}" @selected($s->priorite === $cle)>{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        <x-cc.bouton type="submit" variante="secondaire">OK</x-cc.bouton>
                    </form>
                </x-cc.bloc>
            @endif

            <x-cc.bloc titre="Historique">
                <x-cc.historique :etapes="$s->historique" />
            </x-cc.bloc>
        </div>
    </div>

    {{-- ===== Dialogues de confirmation ===== --}}
    <x-cc.dialogue id="dialogue-rejet" titre="Rejeter ce signalement ?" confirmer="Confirmer le rejet" ton="danger" formulaire="form-rejet">
        <p>Le citoyen sera informé du rejet et recevra le motif ci-dessous. Cette action clôt le dossier.</p>
        <form id="form-rejet" method="POST" action="{{ route('responsable.signalements.rejeter', $s->id) }}" class="space-y-2">
            @csrf
            <label for="motif" class="block text-sm font-medium text-zinc-900">Motif expliqué au citoyen <span class="text-red-700" aria-hidden="true">*</span></label>
            <select class="cc-input" onchange="if(this.value){document.getElementById('motif').value=this.value}" aria-label="Motifs fréquents">
                <option value="">Motifs fréquents…</option>
                <option>Ce lieu fait déjà l'objet d'un signalement en cours de traitement.</option>
                <option>Le problème n'a pas pu être constaté à l'adresse indiquée.</option>
                <option>Ce lieu ne relève pas de la compétence de la commune.</option>
            </select>
            <textarea id="motif" name="motif" rows="3" class="cc-input" required minlength="10" placeholder="Expliquez simplement pourquoi le signalement n'est pas retenu."></textarea>
        </form>
    </x-cc.dialogue>

    <x-cc.dialogue id="dialogue-affecter" titre="Confirmer l'affectation" confirmer="Affecter la mission">
        <p>La mission <strong>{{ $s->reference }}</strong> sera confiée à <strong data-remplir="agent_id"></strong>, qui sera notifié(e). Le citoyen verra le statut « Affecté ».</p>
        <div class="rounded-lg bg-sable-100 p-3"><p class="text-xs font-medium text-zinc-600">Consignes</p><p data-remplir="consignes" class="whitespace-pre-line"></p></div>
    </x-cc.dialogue>

    <x-cc.dialogue id="dialogue-cloturer" titre="Valider la résolution ?" confirmer="Valider et clôturer" ton="succes" formulaire="form-cloturer">
        <p>Le dossier passera au statut <strong>Vérifié</strong> et sera clôturé. Le citoyen sera informé que le problème est réglé.</p>
        <form id="form-cloturer" method="POST" action="{{ route('responsable.signalements.cloturer', $s->id) }}">@csrf</form>
    </x-cc.dialogue>

    <x-cc.dialogue id="dialogue-reprise" titre="Demander une reprise" confirmer="Renvoyer à l'agent" formulaire="form-reprise">
        <p>Le dossier repassera « En cours » et {{ $s->agent?->name ?? 'l\'agent' }} sera notifié(e).</p>
        <form id="form-reprise" method="POST" action="{{ route('responsable.signalements.reprise', $s->id) }}" class="space-y-2">
            @csrf
            <label for="motif-reprise" class="block text-sm font-medium text-zinc-900">Ce qui reste à faire <span class="text-red-700" aria-hidden="true">*</span></label>
            <textarea id="motif-reprise" name="motif" rows="3" class="cc-input" required minlength="5"></textarea>
        </form>
    </x-cc.dialogue>
</x-layouts::espace>
