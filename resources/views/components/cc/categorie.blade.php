@props(['categorie', 'avecIcone' => true])
@php
    $cle = $categorie instanceof \BackedEnum ? $categorie->value : $categorie;
    $c = config("cleancomores.categories.$cle", ['label' => ucfirst((string) $cle), 'icone' => 'dots']);
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    @if ($avecIcone)<x-cc.icon :name="$c['icone']" class="size-4 shrink-0 text-lagon-700" />@endif
    <span>{{ $c['label'] }}</span>
</span>
