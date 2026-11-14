{{-- Suivi d'un signalement par le citoyen. Variable : $signalement (voir FRONTEND.md) --}}
@php
    $s = $signalement;
    $infoStatut = config('cleancomores.statuts.'.$s->statut);
@endphp
<x-layouts::espace role="citoyen" :title="'Signalement '.$s->reference">
    <x-cc.entete-page :titre="config('cleancomores.categories.'.$s->categorie.'.label')" :retour="route('citoyen.signalements.index')" retour-label="Mes signalements">
        <p class="mt-1 font-mono text-sm text-zinc-600">{{ $s->reference }} · envoyé le {{ $s->created_at->translatedFormat('d M Y') }}</p>
    </x-cc.entete-page>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="space-y-6 lg:col-span-3">
            <x-cc.bloc>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-cc.statut :statut="$s->statut" taille="lg" />
                    <span class="text-sm text-zinc-600">Mis à jour {{ $s->updated_at->diffForHumans() }}</span>
                </div>
                <p class="mt-3 text-zinc-800">{{ $infoStatut['description'] ?? '' }}</p>
                <x-cc.progression :statut="$s->statut" class="mt-4" />

                @if ($s->statut === 'rejete' && $s->motif_rejet)
                    <div class="mt-4 rounded-lg bg-red-50 p-4 ring-1 ring-red-200">
                        <p class="text-sm font-semibold text-red-900">Motif communiqué par l'équipe municipale</p>
                        <p class="mt-1 text-sm text-red-900">{{ $s->motif_rejet }}</p>
                    </div>
                @endif
            </x-cc.bloc>

            <x-cc.bloc titre="Historique du dossier" description="Chaque étape est horodatée.">
                <x-cc.historique :etapes="$s->historique" />
            </x-cc.bloc>
        </div>

        <div class="space-y-6 lg:col-span-2">
            <x-cc.bloc titre="Votre signalement">
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-zinc-600">Type</dt><dd class="font-medium"><x-cc.categorie :categorie="$s->categorie" /></dd></div>
                    <div><dt class="text-zinc-600">Description</dt><dd class="whitespace-pre-line">{{ $s->description }}</dd></div>
                    <div><dt class="text-zinc-600">Lieu</dt><dd class="font-medium">{{ $s->commune }}@if ($s->repere) — {{ $s->repere }}@endif</dd></div>
                </dl>
                <div class="mt-4"><x-cc.photo :url="$s->photo_url" /></div>
            </x-cc.bloc>

            @if ($s->latitude && $s->longitude)
                <x-cc.carte :points="[['lat' => $s->latitude, 'lng' => $s->longitude, 'titre' => $s->repere ?? $s->commune, 'statut' => $s->statut]]" hauteur="h-56" label="Emplacement du signalement" />
            @endif

            @if ($s->compte_rendu && in_array($s->statut, ['resolu', 'verifie']))
                <x-cc.bloc titre="Intervention réalisée">
                    <p class="text-sm">{{ $s->compte_rendu->texte }}</p>
                    @if ($s->compte_rendu->photo_url)<div class="mt-3"><x-cc.photo :url="$s->compte_rendu->photo_url" alt="Photo après intervention" /></div>@endif
                </x-cc.bloc>
            @endif
        </div>
    </div>
</x-layouts::espace>
