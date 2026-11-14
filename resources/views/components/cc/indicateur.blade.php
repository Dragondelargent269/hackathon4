{{-- Tuile d'indicateur (KPI) --}}
@props(['label', 'valeur', 'icone' => 'chart', 'aide' => null, 'ton' => 'neutre', 'href' => null])
@php
    $tons = ['neutre' => 'bg-zinc-100 text-zinc-700', 'alerte' => 'bg-red-50 text-red-700', 'attention' => 'bg-amber-50 text-amber-800', 'succes' => 'bg-emerald-50 text-emerald-700', 'lagon' => 'bg-lagon-50 text-lagon-800'];
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-xl border border-zinc-200 bg-white p-4 shadow-xs'.($href ? ' transition-colors hover:border-lagon-600' : '')]) }}>
    <span class="grid size-10 shrink-0 place-items-center rounded-lg {{ $tons[$ton] ?? $tons['neutre'] }}"><x-cc.icon :name="$icone" /></span>
    <span class="min-w-0">
        <span class="block text-sm text-zinc-600">{{ $label }}</span>
        <span class="block text-2xl font-semibold tabular-nums text-zinc-900">{{ $valeur }}</span>
        @if ($aide)<span class="block text-xs text-zinc-500">{{ $aide }}</span>@endif
    </span>
</{{ $tag }}>
