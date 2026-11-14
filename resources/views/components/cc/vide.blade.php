{{-- État vide avec slot nommé « action » --}}
@props(['titre', 'icone' => 'inbox'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-xl border border-dashed border-zinc-300 bg-white px-6 py-10 text-center']) }}>
    <span class="grid size-12 place-items-center rounded-full bg-lagon-50 text-lagon-700"><x-cc.icon :name="$icone" class="size-6" /></span>
    <h3 class="mt-3 font-semibold text-zinc-900">{{ $titre }}</h3>
    <div class="mt-1 max-w-sm text-sm text-zinc-600">{{ $slot }}</div>
    @isset($action)<div class="mt-4">{{ $action }}</div>@endisset
</div>
