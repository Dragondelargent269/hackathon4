{{-- Message d'état. type : succes | erreur | info | attention --}}
@props(['type' => 'info', 'titre' => null])
@php
    $t = [
        'succes' => ['bg-emerald-50 text-emerald-900 ring-emerald-300', 'check-circle', 'status'],
        'erreur' => ['bg-red-50 text-red-900 ring-red-300', 'alert', 'alert'],
        'attention' => ['bg-amber-50 text-amber-900 ring-amber-300', 'alert', 'status'],
        'info' => ['bg-sky-50 text-sky-900 ring-sky-300', 'info', 'status'],
    ][$type] ?? ['bg-sky-50 text-sky-900 ring-sky-300', 'info', 'status'];
@endphp
<div role="{{ $t[2] }}" {{ $attributes->merge(['class' => 'flex gap-3 rounded-lg p-3 text-sm ring-1 ring-inset '.$t[0]]) }}>
    <x-cc.icon :name="$t[1]" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0">
        @if ($titre)<p class="font-semibold">{{ $titre }}</p>@endif
        <div>{{ $slot }}</div>
    </div>
</div>
