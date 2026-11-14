{{-- Historique horodaté des transitions d'un dossier.
     $etapes : itérable d'objets { statut, created_at (Carbon), auteur (string|null), commentaire (string|null) }
     Ordre attendu : du plus ancien au plus récent ; affiché du plus récent au plus ancien. --}}
@props(['etapes', 'masquerAuteurs' => false])
@php $etapes = collect($etapes)->reverse()->values(); @endphp
<ol {{ $attributes->merge(['class' => 'relative space-y-5']) }}>
    @foreach ($etapes as $i => $e)
        @php $s = config('cleancomores.statuts.'.($e->statut instanceof \BackedEnum ? $e->statut->value : $e->statut)); @endphp
        <li class="relative flex gap-3">
            @unless ($loop->last)
                <span class="absolute start-[15px] top-8 -bottom-5 w-px bg-zinc-200" aria-hidden="true"></span>
            @endunless
            <span class="relative z-10 grid size-8 shrink-0 place-items-center rounded-full ring-4 ring-white {{ $i === 0 ? ($s['point'] ?? 'bg-zinc-500').' text-white' : 'bg-zinc-100 text-zinc-600' }}">
                <x-cc.icon :name="$s['icone'] ?? 'dots'" class="size-4" />
            </span>
            <div class="min-w-0 flex-1 pb-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="font-medium text-zinc-900">{{ $s['label'] ?? $e->statut }}</p>
                    @if ($i === 0)<span class="rounded bg-zinc-900 px-1.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-white">Actuel</span>@endif
                </div>
                <p class="text-sm text-zinc-600">
                    <time datetime="{{ $e->created_at->toIso8601String() }}">{{ $e->created_at->translatedFormat('d M Y à H:i') }}</time>
                    @if (! $masquerAuteurs && ! empty($e->auteur)) · {{ $e->auteur }}@endif
                </p>
                @if (! empty($e->commentaire))
                    <p class="mt-1.5 rounded-lg bg-sable-100 px-3 py-2 text-sm text-zinc-800">{{ $e->commentaire }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
