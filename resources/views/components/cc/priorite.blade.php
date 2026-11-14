{{-- Badge de priorité : libellé + jauge de 1 à 4 barres (pas seulement la couleur) --}}
@props(['priorite' => null])
@php
    $cle = $priorite instanceof \BackedEnum ? $priorite->value : $priorite;
    $p = $cle ? config("cleancomores.priorites.$cle") : null;
@endphp
@if ($p)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-2 py-0.5 text-xs font-semibold ring-1 ring-inset '.$p['classes']]) }}>
        <span class="flex items-end gap-px" aria-hidden="true">
            @for ($i = 1; $i <= 4; $i++)
                <span class="w-1 rounded-sm {{ $i <= $p['barres'] ? 'bg-current' : 'bg-current/25' }}" style="height: {{ 4 + $i * 2 }}px"></span>
            @endfor
        </span>
        <span><span class="sr-only">Priorité : </span>{{ $p['label'] }}</span>
    </span>
@else
    <span {{ $attributes->merge(['class' => 'text-xs text-zinc-500']) }}>Priorité non définie</span>
@endif
