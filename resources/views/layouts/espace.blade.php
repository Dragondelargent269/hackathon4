{{--
    Layout des espaces connectés.
    Usage : <x-layouts::espace role="citoyen|responsable|agent" title="…"> … </x-layouts::espace>
    - Ordinateur : barre latérale ; mobile : barre du haut + barre d'onglets en bas (cibles ≥ 44 px).
    - L'utilisateur affiché vient de $utilisateur (maquette) ou auth()->user().
--}}
@props(['role', 'title' => null, 'large' => false])
@php
    // Utilisateur connecté ; en maquette, un utilisateur fictif selon le rôle (voir routes/maquette.php)
    $user = auth()->user() ?? (app()->bound('maquette.utilisateur') ? app('maquette.utilisateur')($role) : null);
    $navs = [
        'citoyen' => [
            ['route' => 'citoyen.signalements.index', 'actif' => 'citoyen.signalements.index|citoyen.signalements.show', 'label' => 'Mes signalements', 'court' => 'Signalements', 'icone' => 'list'],
            ['route' => 'citoyen.signalements.create', 'actif' => 'citoyen.signalements.create', 'label' => 'Signaler un problème', 'court' => 'Signaler', 'icone' => 'plus', 'cta' => true],
            ['route' => 'citoyen.profil.edit', 'actif' => 'citoyen.profil.*', 'label' => 'Mon profil', 'court' => 'Profil', 'icone' => 'user'],
        ],
        'responsable' => [
            ['route' => 'responsable.tableau', 'actif' => 'responsable.tableau', 'label' => 'Vue d\'ensemble', 'court' => 'Accueil', 'icone' => 'home'],
            ['route' => 'responsable.signalements.index', 'actif' => 'responsable.signalements.*', 'label' => 'Signalements', 'court' => 'Dossiers', 'icone' => 'list'],
            ['route' => 'responsable.statistiques', 'actif' => 'responsable.statistiques', 'label' => 'Statistiques', 'court' => 'Stats', 'icone' => 'chart'],
        ],
        'agent' => [
            ['route' => 'agent.missions.index', 'actif' => 'agent.missions.*', 'label' => 'Mes missions', 'court' => 'Missions', 'icone' => 'truck'],
            ['route' => 'agent.historique', 'actif' => 'agent.historique', 'label' => 'Historique', 'court' => 'Historique', 'icone' => 'clock'],
        ],
    ];
    $roles = ['citoyen' => 'Citoyen', 'responsable' => 'Responsable municipal', 'agent' => 'Agent de terrain'];
    $nav = $navs[$role] ?? [];
    $estActif = fn ($item) => request()->routeIs(...explode('|', $item['actif']));
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.cc-head', ['title' => $title])
</head>
<body class="cc-body min-h-screen pb-20 lg:pb-0">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Aller au contenu</a>
    @include('partials.cc-bandeau-maquette')

    <div class="lg:flex">
        {{-- Barre latérale (ordinateur) --}}
        <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col bg-lagon-950 text-lagon-50 lg:flex">
            <div class="px-5 py-5"><x-cc.logo clair /></div>
            <p class="px-5 text-xs font-semibold uppercase tracking-wider text-lagon-300">{{ $roles[$role] ?? '' }}</p>
            <nav class="mt-2 flex-1 space-y-1 px-3" aria-label="Navigation principale">
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}"
                       @if ($estActif($item)) aria-current="page" @endif
                       @class([
                           'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm font-medium transition-colors',
                           'bg-white text-lagon-900' => $estActif($item),
                           'text-lagon-100 hover:bg-white/10' => ! $estActif($item),
                       ])>
                        <x-cc.icon :name="$item['icone']" />{{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            @if ($user)
                <div class="border-t border-white/10 p-4">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-full bg-lagon-700 font-semibold">{{ mb_substr($user->name, 0, 1) }}</span>
                        <div class="min-w-0 text-sm">
                            <p class="truncate font-medium text-white">{{ $user->name }}</p>
                            <p class="truncate text-lagon-200">{{ $user->commune ?? ($roles[$role] ?? '') }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}" class="mt-3">
                        @csrf
                        <button type="submit" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 text-sm text-lagon-100 hover:bg-white/10">
                            <x-cc.icon name="logout" />Se déconnecter
                        </button>
                    </form>
                </div>
            @endif
        </aside>

        <div class="min-w-0 flex-1">
            {{-- Barre du haut (mobile) --}}
            <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-zinc-200 bg-white/95 px-4 backdrop-blur lg:hidden">
                <x-cc.logo />
                <span class="rounded-full bg-lagon-50 px-2.5 py-1 text-xs font-medium text-lagon-800">{{ $roles[$role] ?? '' }}</span>
            </header>

            <main id="contenu" class="mx-auto w-full {{ $large ? 'max-w-7xl' : 'max-w-5xl' }} px-4 py-6 sm:px-6 lg:py-10">
                @if (session('success') || session('error') || $errors->any())
                    <x-cc.flash class="mb-6" />
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Barre d'onglets (mobile) --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-zinc-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Navigation principale">
        <ul class="mx-auto flex max-w-md justify-around">
            @foreach ($nav as $item)
                <li class="flex-1">
                    <a href="{{ route($item['route']) }}"
                       @if ($estActif($item)) aria-current="page" @endif
                       @class([
                           'flex min-h-16 flex-col items-center justify-center gap-0.5 text-xs font-medium',
                           'text-lagon-800' => $estActif($item),
                           'text-zinc-600' => ! $estActif($item),
                       ])>
                        @if (! empty($item['cta']))
                            <span class="grid size-9 place-items-center rounded-full bg-lagon-700 text-white shadow"><x-cc.icon :name="$item['icone']" /></span>
                        @else
                            <x-cc.icon :name="$item['icone']" class="size-6" />
                        @endif
                        {{ $item['court'] }}
                    </a>
                </li>
            @endforeach
            @if ($user)
                <li class="flex-1">
                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                        @csrf
                        <button type="submit" class="flex min-h-16 w-full flex-col items-center justify-center gap-0.5 text-xs font-medium text-zinc-600">
                            <x-cc.icon name="logout" class="size-6" />Quitter
                        </button>
                    </form>
                </li>
            @endif
        </ul>
    </nav>

    @stack('scripts')
</body>
</html>
