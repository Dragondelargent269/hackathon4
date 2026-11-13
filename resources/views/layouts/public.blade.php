{{-- Layout public (accueil, connexion, inscription). Usage : <x-layouts::public title="…"> … </x-layouts::public> --}}
@props(['title' => null, 'centre' => false])
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.cc-head', ['title' => $title])
</head>
<body class="cc-body min-h-screen">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Aller au contenu</a>
    @include('partials.cc-bandeau-maquette')

    <header class="border-b border-zinc-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('home') }}" class="cc-touch flex items-center rounded-lg" aria-label="CleanComores — accueil"><x-cc.logo /></a>
            <nav class="flex items-center gap-1" aria-label="Compte">
                <x-cc.bouton variante="fantome" :href="route('connexion')">Se connecter</x-cc.bouton>
                <x-cc.bouton :href="route('inscription')" class="max-sm:hidden">Créer un compte</x-cc.bouton>
            </nav>
        </div>
    </header>

    <main id="contenu" @class(['px-4 py-8 sm:px-6', 'grid min-h-[calc(100vh-4rem)] place-items-start sm:place-items-center' => $centre])>
        {{ $slot }}
    </main>

    @isset($pied)
        {{ $pied }}
    @else
        <footer class="border-t border-zinc-200 bg-white px-4 py-6 text-center text-sm text-zinc-600">
            CleanComores — plateforme citoyenne de signalement des déchets · Ngazidja · Ndzuani · Mwali
        </footer>
    @endisset
</body>
</html>
