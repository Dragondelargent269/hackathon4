{{-- Bouton ou lien-bouton. Hauteur min. 44 px.
     variante : primaire | secondaire | danger | succes | fantome --}}
@props(['variante' => 'primaire', 'href' => null, 'icone' => null, 'type' => 'button', 'pleine' => false])
@php
    $variantes = [
        'primaire' => 'bg-lagon-700 text-white hover:bg-lagon-800 shadow-sm',
        'secondaire' => 'bg-white text-zinc-900 ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50',
        'danger' => 'bg-red-700 text-white hover:bg-red-800 shadow-sm',
        'succes' => 'bg-emerald-700 text-white hover:bg-emerald-800 shadow-sm',
        'fantome' => 'text-zinc-700 hover:bg-zinc-100',
    ];
    $classes = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50 '
        .($variantes[$variante] ?? $variantes['primaire']).($pleine ? ' w-full' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icone)<x-cc.icon :name="$icone" class="size-5 shrink-0" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icone)<x-cc.icon :name="$icone" class="size-5 shrink-0" />@endif
        {{ $slot }}
    </button>
@endif
