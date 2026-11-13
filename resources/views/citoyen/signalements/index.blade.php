{{-- Variables : $signalements (itérable de signalements du citoyen connecté, ou paginator) --}}
<x-layouts::espace role="citoyen" title="Mes signalements">
    <x-cc.entete-page titre="Mes signalements" sous-titre="Suivez la prise en charge de chacun de vos signalements.">
        <x-slot:actions>
            <x-cc.bouton :href="route('citoyen.signalements.create')" icone="plus">Signaler un problème</x-cc.bouton>
        </x-slot:actions>
    </x-cc.entete-page>

    @php
        $liste = collect(method_exists($signalements, 'items') ? $signalements->items() : $signalements);
        $enCours = $liste->whereNotIn('statut', ['verifie', 'rejete'])->count();
        $termines = $liste->where('statut', 'verifie')->count();
    @endphp

    @if ($liste->isEmpty())
        <x-cc.vide titre="Aucun signalement pour l'instant" icone="leaf">
            Vous avez repéré un dépôt sauvage ou une poubelle qui déborde ? Signalez-le en moins d'une minute.
            <x-slot:action>
                <x-cc.bouton :href="route('citoyen.signalements.create')" icone="plus">Faire mon premier signalement</x-cc.bouton>
            </x-slot:action>
        </x-cc.vide>
    @else
        <dl class="mb-6 grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-4"><dt class="text-xs text-zinc-600 sm:text-sm">Total</dt><dd class="text-2xl font-semibold tabular-nums">{{ $liste->count() }}</dd></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-4"><dt class="text-xs text-zinc-600 sm:text-sm">En traitement</dt><dd class="text-2xl font-semibold tabular-nums">{{ $enCours }}</dd></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-4"><dt class="text-xs text-zinc-600 sm:text-sm">Nettoyés</dt><dd class="text-2xl font-semibold tabular-nums text-emerald-800">{{ $termines }}</dd></div>
        </dl>

        <ul class="space-y-3">
            @foreach ($liste as $s)
                <x-cc.signalement-ligne :signalement="$s" :href="route('citoyen.signalements.show', $s->id)" />
            @endforeach
        </ul>

        @if (method_exists($signalements, 'links'))<div class="mt-6">{{ $signalements->links() }}</div>@endif
    @endif
</x-layouts::espace>
