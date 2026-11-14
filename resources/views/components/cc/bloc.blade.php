{{-- Bloc de contenu avec titre, description et slot nommé « actions » --}}
@props(['titre' => null, 'description' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200 bg-white shadow-xs']) }}>
    @if ($titre || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-100 px-4 py-3 sm:px-5">
            <div>
                @if ($titre)<h2 class="text-base font-semibold text-zinc-900">{{ $titre }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-sm text-zinc-600">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="{{ $flush ? '' : 'p-4 sm:p-5' }}">{{ $slot }}</div>
</section>
