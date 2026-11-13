{{-- Vérification d'un reçu : saisie de la référence (le scan du QR code ouvre directement le résultat) --}}
<x-layouts::espace role="agent" title="Vérifier un reçu">
    <x-cc.entete-page titre="Vérifier un reçu" sous-titre="Ne vous fiez jamais à une capture d'écran : vérifiez le reçu dans l'application." />

    <div class="grid gap-6 md:grid-cols-2">
        <x-cc.bloc titre="Saisir la référence">
            <form method="GET" action="{{ route('agent.recus.index') }}" class="space-y-4">
                <x-cc.champ label="Référence du reçu" pour="reference" requis aide="Elle commence par PAY-, ex. : PAY-7KQ4M9XA.">
                    <input id="reference" name="reference" type="text" required autocapitalize="characters" autocomplete="off" spellcheck="false"
                           pattern="[Pp][Aa][Yy]-[A-Za-z0-9]{8}" class="cc-input font-mono uppercase" aria-describedby="reference-aide" value="{{ request('reference') }}">
                </x-cc.champ>
                <x-cc.bouton type="submit" icone="search" pleine>Vérifier</x-cc.bouton>
            </form>
        </x-cc.bloc>
        <x-cc.bloc titre="Scanner le QR code">
            <p class="text-sm text-zinc-700">Scannez le QR code du reçu avec l'appareil photo de votre téléphone : il ouvre cette application sur la page de vérification, avec un code secret qui prouve que le reçu a bien été émis par CleanComores.</p>
            <p class="mt-3 text-sm text-zinc-700">Le résultat affiché vient <strong>toujours de la base de données</strong> : statut du paiement, validité de l'abonnement et collectes restantes.</p>
        </x-cc.bloc>
    </div>
</x-layouts::espace>
