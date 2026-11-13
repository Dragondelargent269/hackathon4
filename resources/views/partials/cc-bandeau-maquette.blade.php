{{-- Bandeau visible uniquement en mode maquette (données fictives) : navigation rapide entre les rôles --}}
@if ($maquette ?? false)
    <div class="bg-zinc-900 px-4 py-1.5 text-xs text-zinc-200">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-4 gap-y-1">
            <span class="font-semibold text-amber-300">Maquette — données fictives</span>
            <nav aria-label="Changer de rôle (maquette)" class="flex flex-wrap gap-x-3">
                <a class="underline-offset-2 hover:underline" href="{{ route('home') }}">Accueil</a>
                <a class="underline-offset-2 hover:underline" href="{{ route('connexion') }}">Connexion</a>
                <a class="underline-offset-2 hover:underline" href="{{ route('inscription') }}">Inscription</a>
                <a class="underline-offset-2 hover:underline" href="{{ route('citoyen.signalements.index') }}">Citoyen</a>
                <a class="underline-offset-2 hover:underline" href="{{ route('responsable.tableau') }}">Responsable</a>
                <a class="underline-offset-2 hover:underline" href="{{ route('agent.missions.index') }}">Agent</a>
            </nav>
        </div>
    </div>
@endif
