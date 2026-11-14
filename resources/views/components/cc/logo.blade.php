@props(['clair' => false])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-semibold tracking-tight']) }}>
    <span class="grid size-9 place-items-center rounded-lg {{ $clair ? 'bg-white/15 text-white' : 'bg-lagon-700 text-white' }}"><x-cc.icon name="leaf" class="size-5" /></span>
    <span class="text-lg {{ $clair ? 'text-white' : 'text-zinc-900' }}">Clean<span class="{{ $clair ? 'text-lagon-200' : 'text-lagon-700' }}">Comores</span></span>
</span>
