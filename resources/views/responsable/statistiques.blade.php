{{-- Statistiques. Variable : $stats = [
        'mois' => [['mois' => 'Juin', 'recus' => int, 'resolus' => int], ...],
        'zones' => [['zone' => 'ngazidja', 'total' => int, 'resolus' => int], ...],
        'categories' => [['categorie' => 'plastique', 'total' => int], ...],
        'delai_moyen_jours' => float ] --}}
@php
    $mois = collect($stats['mois']);
    $zones = collect($stats['zones']);
    $cats = collect($stats['categories']);
    $totalRecus = $mois->sum('recus');
    $totalResolus = $mois->sum('resolus');
    $taux = $totalRecus ? round($totalResolus / $totalRecus * 100) : 0;
    $max = max(1, $mois->max('recus'));
    $pas = $max > 50 ? 20 : 10;
    $plafond = (int) ceil($max / $pas) * $pas;
    $maxZone = max(1, $zones->max('total'));
    $maxCat = max(1, $cats->max('total'));
@endphp
<x-layouts::espace role="responsable" title="Statistiques" large>
    @push('head')
        <style>
            .viz { --serie-recus: #2a78d6; --serie-resolus: #1baf7a; --grille: #e7e5e4; }
        </style>
    @endpush

    <x-cc.entete-page titre="Statistiques" sous-titre="Activité des 6 derniers mois, toutes zones confondues." />

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-cc.indicateur label="Signalements reçus" :valeur="$totalRecus" icone="inbox" />
        <x-cc.indicateur label="Résolus" :valeur="$totalResolus" icone="check-circle" ton="succes" />
        <x-cc.indicateur label="Taux de résolution" :valeur="$taux.' %'" icone="chart" ton="lagon" />
        <x-cc.indicateur label="Délai moyen" :valeur="str_replace('.', ',', $stats['delai_moyen_jours']).' j'" icone="clock" aide="Du signalement à la résolution" />
    </div>

    <div class="viz mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Reçus / résolus par mois --}}
        <x-cc.bloc titre="Reçus et résolus par mois" class="xl:col-span-2">
            <x-slot:actions>
                <ul class="flex flex-wrap gap-4 text-sm text-zinc-700" aria-label="Légende">
                    <li class="flex items-center gap-2"><span class="size-3 rounded-sm" style="background: var(--serie-recus)"></span>Reçus</li>
                    <li class="flex items-center gap-2"><span class="size-3 rounded-sm" style="background: var(--serie-resolus)"></span>Résolus</li>
                </ul>
            </x-slot:actions>

            <figure aria-labelledby="fig-mois-desc">
                <p id="fig-mois-desc" class="sr-only">Histogramme : nombre de signalements reçus et résolus pour chaque mois. Les valeurs détaillées sont dans le tableau ci-dessous.</p>
                <div class="flex gap-2" aria-hidden="true">
                    {{-- Axe Y --}}
                    <div class="relative h-56 w-7 shrink-0 text-end text-xs tabular-nums text-zinc-500">
                        @for ($v = 0; $v <= $plafond; $v += $pas)
                            <span class="absolute end-0 -translate-y-1/2" style="bottom: {{ $v / $plafond * 100 }}%">{{ $v }}</span>
                        @endfor
                    </div>
                    <div class="relative h-56 flex-1">
                        @for ($v = 0; $v <= $plafond; $v += $pas)
                            <span class="absolute inset-x-0 border-t {{ $v === 0 ? 'border-zinc-400' : 'border-(--grille)' }}" style="bottom: {{ $v / $plafond * 100 }}%"></span>
                        @endfor
                        <div class="relative flex h-full items-end justify-around gap-2">
                            @foreach ($mois as $m)
                                <div class="group relative flex h-full flex-1 items-end justify-center gap-0.5">
                                    <span class="w-full max-w-6 rounded-t-[4px]" style="height: {{ $m['recus'] / $plafond * 100 }}%; background: var(--serie-recus)"></span>
                                    <span class="w-full max-w-6 rounded-t-[4px]" style="height: {{ $m['resolus'] / $plafond * 100 }}%; background: var(--serie-resolus)"></span>
                                    <span class="pointer-events-none absolute bottom-full z-10 mb-1 hidden w-max rounded-lg bg-zinc-900 px-2.5 py-1.5 text-xs text-white shadow-lg group-hover:block">
                                        <strong>{{ $m['mois'] }}</strong><br>Reçus : {{ $m['recus'] }}<br>Résolus : {{ $m['resolus'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="ms-9 mt-2 flex justify-around text-xs text-zinc-600" aria-hidden="true">
                    @foreach ($mois as $m)<span class="flex-1 text-center">{{ $m['mois'] }}</span>@endforeach
                </div>
            </figure>

            <details class="mt-4 rounded-lg border border-zinc-200">
                <summary class="flex min-h-11 cursor-pointer items-center px-3 text-sm font-medium">Voir les données en tableau</summary>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-zinc-600"><tr><th scope="col" class="px-3 py-2 text-start">Mois</th><th scope="col" class="px-3 py-2 text-end">Reçus</th><th scope="col" class="px-3 py-2 text-end">Résolus</th><th scope="col" class="px-3 py-2 text-end">Taux</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 tabular-nums">
                        @foreach ($mois as $m)
                            <tr><th scope="row" class="px-3 py-2 text-start font-medium">{{ $m['mois'] }}</th><td class="px-3 py-2 text-end">{{ $m['recus'] }}</td><td class="px-3 py-2 text-end">{{ $m['resolus'] }}</td><td class="px-3 py-2 text-end">{{ $m['recus'] ? round($m['resolus'] / $m['recus'] * 100) : 0 }} %</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        </x-cc.bloc>

        {{-- Taux de résolution --}}
        <x-cc.bloc titre="Taux de résolution">
            <div class="flex flex-col items-center py-2">
                <svg viewBox="0 0 120 120" class="size-40" role="img" aria-label="Taux de résolution : {{ $taux }} %">
                    <circle cx="60" cy="60" r="50" fill="none" stroke="#e7e5e4" stroke-width="12" />
                    <circle cx="60" cy="60" r="50" fill="none" stroke="var(--serie-resolus)" stroke-width="12" stroke-linecap="round"
                            stroke-dasharray="{{ round(2 * M_PI * 50 * $taux / 100, 1) }} 999" transform="rotate(-90 60 60)" />
                    <text x="60" y="66" text-anchor="middle" class="fill-zinc-900 text-[22px] font-semibold">{{ $taux }} %</text>
                </svg>
                <p class="mt-2 text-center text-sm text-zinc-700">{{ $totalResolus }} signalements résolus sur {{ $totalRecus }} reçus.</p>
            </div>
        </x-cc.bloc>

        {{-- Répartition par zone --}}
        <x-cc.bloc titre="Répartition par zone" class="xl:col-span-2">
            <ul class="space-y-5">
                @foreach ($zones as $z)
                    @php $tz = $z['total'] ? round($z['resolus'] / $z['total'] * 100) : 0; @endphp
                    <li>
                        <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span class="font-medium">{{ config('cleancomores.zones.'.$z['zone'].'.label') }}</span>
                            <span class="tabular-nums text-zinc-700"><strong class="text-zinc-900">{{ $z['total'] }}</strong> signalements · {{ $tz }} % résolus</span>
                        </div>
                        <div class="mt-1.5 h-3 rounded-full bg-zinc-100" role="img" aria-label="{{ $z['total'] }} signalements, dont {{ $z['resolus'] }} résolus">
                            <div class="flex h-full gap-0.5 overflow-hidden rounded-full" style="width: {{ $z['total'] / $maxZone * 100 }}%">
                                <span class="h-full" style="width: {{ $tz }}%; background: var(--serie-resolus)"></span>
                                <span class="h-full flex-1" style="background: var(--serie-recus)"></span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <ul class="mt-5 flex flex-wrap gap-4 text-xs text-zinc-700" aria-hidden="true">
                <li class="flex items-center gap-2"><span class="size-3 rounded-sm" style="background: var(--serie-resolus)"></span>Résolus</li>
                <li class="flex items-center gap-2"><span class="size-3 rounded-sm" style="background: var(--serie-recus)"></span>Non résolus</li>
            </ul>
        </x-cc.bloc>

        {{-- Par catégorie --}}
        <x-cc.bloc titre="Par catégorie">
            <ul class="space-y-4">
                @foreach ($cats as $c)
                    <li>
                        <div class="flex items-center justify-between text-sm"><x-cc.categorie :categorie="$c['categorie']" /><span class="font-semibold tabular-nums">{{ $c['total'] }}</span></div>
                        <div class="mt-1.5 h-2 rounded-full bg-zinc-100" aria-hidden="true"><div class="h-full rounded-full" style="width: {{ $c['total'] / $maxCat * 100 }}%; background: var(--serie-recus)"></div></div>
                    </li>
                @endforeach
            </ul>
        </x-cc.bloc>
    </div>
</x-layouts::espace>
