{{--
    Fenêtre de confirmation (<dialog> natif, géré par resources/js/app.js).
    Ouvrir :  <button data-ouvrir="ID">                 → le dialogue contient lui-même le formulaire (attribut formulaire="id-du-form")
          ou  <form data-confirmer="ID">                → le formulaire n'est envoyé qu'après clic sur « confirmer »
    Dans le slot, <span data-remplir="nom_du_champ"></span> affiche la valeur choisie dans le formulaire déclencheur.
--}}
@props(['id', 'titre', 'confirmer' => 'Confirmer', 'ton' => 'primaire', 'formulaire' => null])
<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-titre" class="cc-dialog m-auto w-[calc(100%-2rem)] max-w-lg rounded-2xl bg-white p-0 text-zinc-900 shadow-2xl">
    <div class="p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-titre" class="text-lg font-semibold">{{ $titre }}</h2>
            <button type="button" data-fermer class="cc-touch -m-2 grid place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100" aria-label="Fermer">
                <x-cc.icon name="x" />
            </button>
        </div>
        <div class="mt-3 space-y-4 text-sm text-zinc-700">{{ $slot }}</div>
        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-cc.bouton variante="secondaire" data-fermer>Annuler</x-cc.bouton>
            @if ($formulaire)
                <x-cc.bouton :variante="$ton" type="submit" form="{{ $formulaire }}">{{ $confirmer }}</x-cc.bouton>
            @else
                <x-cc.bouton :variante="$ton" data-valider>{{ $confirmer }}</x-cc.bouton>
            @endif
        </div>
    </div>
</dialog>
