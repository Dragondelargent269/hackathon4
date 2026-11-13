{{-- Collectes à domicile à effectuer (paiement confirmé). Variables : $demandes, $commune, $communes --}}
<x-layouts::espace role="agent" title="Collectes à domicile" large>
    <x-cc.entete-page titre="Collectes à domicile" :sous-titre="$demandes->count().' demande(s) payée(s) en attente de passage.'">
        <x-slot:actions>
            <x-cc.bouton variante="secondaire" :href="route('agent.recus.index')" icone="qr">Vérifier un reçu</x-cc.bouton>
            <x-cc.bouton variante="fantome" :href="route('agent.collectes.historique')" icone="clock">Mes collectes effectuées</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    <form method="GET" data-filtres-auto class="mb-4 flex flex-wrap items-end gap-3">
        <x-cc.champ label="Commune" pour="commune" class="w-full sm:w-64">
            <select id="commune" name="commune" class="cc-input">
                <option value="">Toutes les communes</option>
                @foreach ($communes as $c)
                    <option value="{{ $c }}" @selected($commune === $c)>{{ $c }}</option>
                @endforeach
            </select>
        </x-cc.champ>
        <noscript><x-cc.bouton type="submit" variante="secondaire">Filtrer</x-cc.bouton></noscript>
    </form>

    @if ($demandes->isEmpty())
        <x-cc.vide titre="Aucune collecte à effectuer" icone="truck">Les demandes des citoyens abonnés (paiement confirmé) apparaîtront ici.</x-cc.vide>
    @else
        <div class="grid gap-6 lg:grid-cols-5">
            <ul class="space-y-3 lg:col-span-2">
                @foreach ($demandes as $d)
                    <li>
                        <a href="{{ route('agent.collectes.show', $d) }}" class="block rounded-xl border border-zinc-200 bg-white p-4 shadow-xs hover:border-lagon-600">
                            <span class="flex items-start justify-between gap-2">
                                <span class="font-semibold">{{ $d->abonnement->adresse }}</span>
                                <x-cc.etat groupe="paiements" valeur="confirme" taille="sm" />
                            </span>
                            <span class="mt-1 block text-sm text-zinc-600">{{ $d->abonnement->commune }} · {{ $d->abonnement->citoyen->name }}</span>
                            <span class="block text-sm text-zinc-600">{{ config('cleancomores.creneaux.'.$d->creneau) }} · demandée le {{ $d->created_at->translatedFormat('j M à H:i') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="lg:col-span-3">
                <x-cc.carte class="lg:sticky lg:top-6" hauteur="h-96 lg:h-[70vh]" label="Carte des collectes à domicile"
                    :points="$demandes->map(fn ($d) => $d->abonnement->pourCarte(route('agent.collectes.show', $d), $d->abonnement->citoyen->name.' · '.config('cleancomores.creneaux.'.$d->creneau)))" legende />
            </div>
        </div>
    @endif
</x-layouts::espace>
