{{-- En-tête de page : titre, sous-titre, lien retour, slot nommé « actions » --}}
@props(['titre', 'sousTitre' => null, 'retour' => null, 'retourLabel' => 'Retour'])
<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    @if ($retour)
        <a href="{{ $retour }}" class="cc-touch -ms-2 mb-1 inline-flex items-center gap-1 rounded-lg px-2 text-sm font-medium text-lagon-800 hover:underline">
            <x-cc.icon name="arrow-left" class="size-4" />{{ $retourLabel }}
        </a>
    @endif
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">{{ $titre }}</h1>
            @if ($sousTitre)<p class="mt-1 text-zinc-600">{{ $sousTitre }}</p>@endif
            {{ $slot }}
        </div>
        @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
    </div>
</div>
