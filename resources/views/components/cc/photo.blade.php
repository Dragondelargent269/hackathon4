@props(['url' => null, 'alt' => 'Photo du signalement'])
@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-zinc-200">
        <img src="{{ $url }}" alt="{{ $alt }}" class="aspect-video w-full object-cover" loading="lazy">
        <span class="sr-only">(ouvrir en grand dans un nouvel onglet)</span>
    </a>
@else
    <div {{ $attributes->merge(['class' => 'grid aspect-video place-items-center rounded-xl border border-dashed border-zinc-300 bg-sable-100 text-sm text-zinc-600']) }}>
        <span class="flex flex-col items-center gap-1"><x-cc.icon name="image" class="size-6" />Aucune photo jointe</span>
    </div>
@endif
