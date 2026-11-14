{{-- Élément de liste cliquable pour un signalement (citoyen / agent / mobile responsable) --}}
@props(['signalement', 'href', 'avecPriorite' => false, 'avecAgent' => false])
@php $s = $signalement; @endphp
<li>
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'group flex gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs transition-colors hover:border-lagon-600']) }}>
        <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-lagon-50 text-lagon-700">
            <x-cc.icon :name="config('cleancomores.categories.'.$s->categorie.'.icone', 'dots')" class="size-6" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-start justify-between gap-2">
                <span class="font-semibold text-zinc-900">{{ config('cleancomores.categories.'.$s->categorie.'.label') }}</span>
                <x-cc.statut :statut="$s->statut" taille="sm" />
            </span>
            <span class="mt-0.5 line-clamp-2 block text-sm text-zinc-700">{{ $s->description }}</span>
            <span class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600">
                <span class="font-mono">{{ $s->reference }}</span>
                <span class="inline-flex items-center gap-1"><x-cc.icon name="map-pin" class="size-3.5" />{{ $s->commune }}</span>
                <span class="inline-flex items-center gap-1"><x-cc.icon name="clock" class="size-3.5" /><time datetime="{{ $s->updated_at->toIso8601String() }}">{{ $s->updated_at->diffForHumans() }}</time></span>
                @if ($avecPriorite)<x-cc.priorite :priorite="$s->priorite" />@endif
                @if ($avecAgent && $s->agent)<span class="inline-flex items-center gap-1"><x-cc.icon name="user" class="size-3.5" />{{ $s->agent->name }}</span>@endif
            </span>
        </span>
        <x-cc.icon name="chevron-right" class="mt-3 size-5 shrink-0 text-zinc-400 group-hover:text-lagon-700" />
    </a>
</li>
