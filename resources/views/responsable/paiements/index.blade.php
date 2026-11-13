{{-- Paiements et collectes à domicile. Variables : $paiements (paginator), $filtres, $indicateurs --}}
<x-layouts::espace role="responsable" title="Collecte et paiements" large>
    <x-cc.entete-page titre="Collecte à domicile et paiements" sous-titre="Les formules et tarifs se gèrent dans l'administration.">
        <x-slot:actions>
            <x-cc.bouton variante="secondaire" :href="url('/admin')" icone="pencil">Gérer les formules</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-cc.indicateur label="Paiements à confirmer" :valeur="$indicateurs['en_attente']" icone="clock" :ton="$indicateurs['en_attente'] ? 'attention' : 'neutre'" :href="route('responsable.paiements.index', ['statut' => 'en_attente'])" />
        <x-cc.indicateur label="Montant confirmé" :valeur="number_format($indicateurs['encaisse'], 0, ',', ' ').' KMF'" icone="wallet" ton="lagon" aide="Simulation : aucun argent réel" />
        <x-cc.indicateur label="Abonnés actifs" :valeur="$indicateurs['abonnes_actifs']" icone="users" ton="succes" />
        <x-cc.indicateur label="Collectes" :valeur="$indicateurs['collectes_attente'].' en attente'" icone="truck" :aide="$indicateurs['collectes_mois'].' effectuée(s) ce mois-ci'" />
    </div>

    <x-cc.alerte type="attention" titre="Paiements en mode démonstration" class="mb-6">
        Aucun opérateur de paiement n'est relié. Confirmez un paiement <strong>uniquement</strong> si vous avez vérifié sa réception par un autre moyen ; chaque confirmation est enregistrée à votre nom.
    </x-cc.alerte>

    <form method="GET" data-filtres-auto class="mb-4 flex flex-wrap items-end gap-3">
        <x-cc.champ label="Statut" pour="statut" class="w-full sm:w-60">
            <select id="statut" name="statut" class="cc-input">
                <option value="">Tous</option>
                @foreach (config('cleancomores.paiements') as $cle => $s)
                    <option value="{{ $cle }}" @selected($filtres['statut'] === $cle)>{{ $s['label'] }}</option>
                @endforeach
            </select>
        </x-cc.champ>
        <x-cc.champ label="Référence ou citoyen" pour="q" class="w-full sm:w-72">
            <input id="q" name="q" type="search" class="cc-input" value="{{ $filtres['q'] }}">
        </x-cc.champ>
        <x-cc.bouton type="submit" variante="secondaire" icone="search">Rechercher</x-cc.bouton>
    </form>

    @if ($paiements->isEmpty())
        <x-cc.vide titre="Aucun paiement" icone="wallet">Aucun paiement ne correspond à ces critères.</x-cc.vide>
    @else
        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <caption class="sr-only">Liste des paiements</caption>
                <thead class="bg-zinc-50 text-zinc-600">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">Référence</th>
                        <th scope="col" class="px-4 py-3 font-medium">Citoyen</th>
                        <th scope="col" class="px-4 py-3 font-medium">Formule</th>
                        <th scope="col" class="px-4 py-3 text-right font-medium">Montant</th>
                        <th scope="col" class="px-4 py-3 font-medium">Statut</th>
                        <th scope="col" class="px-4 py-3 font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    @foreach ($paiements as $p)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="block font-mono font-semibold">{{ $p->reference }}</span>
                                <span class="block text-xs text-zinc-600">{{ $p->created_at->translatedFormat('j M Y H:i') }} · {{ $p->telephone }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $p->citoyen->name }}<span class="block text-xs text-zinc-600">{{ $p->abonnement->commune }}</span></td>
                            <td class="px-4 py-3">{{ $p->abonnement->formule_nom }}<span class="block text-xs text-zinc-600">{{ $p->abonnement->reference }}</span></td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($p->montant, 0, ',', ' ') }} KMF</td>
                            <td class="px-4 py-3">
                                <x-cc.etat groupe="paiements" :valeur="$p->statut" taille="sm" />
                                @if ($p->traitePar)<span class="mt-1 block text-xs text-zinc-600">par {{ $p->traitePar->name }}{{ $p->confirme_at ? ', '.$p->confirme_at->translatedFormat('j M H:i') : '' }}</span>@endif
                                @if ($p->motif_refus)<span class="block text-xs text-zinc-600">{{ $p->motif_refus }}</span>@endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($p->statut === 'en_attente')
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @if ($p->simulation)
                                            <form method="POST" action="{{ route('responsable.paiements.confirmer', $p) }}" data-confirmer="confirmer-{{ $p->id }}">
                                                @csrf
                                                <x-cc.bouton type="submit" variante="succes" icone="check">Confirmer</x-cc.bouton>
                                            </form>
                                            <x-cc.dialogue id="confirmer-{{ $p->id }}" titre="Confirmer le paiement {{ $p->reference }} ?" confirmer="Confirmer le paiement" ton="succes">
                                                {{ number_format($p->montant, 0, ',', ' ') }} KMF de {{ $p->citoyen->name }}. L'abonnement « {{ $p->abonnement->formule_nom }} » sera activé immédiatement. Cette action est enregistrée à votre nom.
                                            </x-cc.dialogue>
                                        @endif
                                        <x-cc.bouton variante="secondaire" data-ouvrir="refuser-{{ $p->id }}">Refuser</x-cc.bouton>
                                        <form id="form-refuser-{{ $p->id }}" method="POST" action="{{ route('responsable.paiements.refuser', $p) }}">@csrf</form>
                                        <x-cc.dialogue id="refuser-{{ $p->id }}" titre="Refuser le paiement {{ $p->reference }} ?" confirmer="Refuser" ton="danger" formulaire="form-refuser-{{ $p->id }}">
                                            <x-cc.champ label="Motif" pour="motif-{{ $p->id }}" requis>
                                                <input id="motif-{{ $p->id }}" name="motif" form="form-refuser-{{ $p->id }}" type="text" required minlength="5" maxlength="255" class="cc-input" value="Paiement non reçu.">
                                            </x-cc.champ>
                                        </x-cc.dialogue>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $paiements->links() }}</div>
    @endif
</x-layouts::espace>
