{{-- Badge d'état pour la collecte à domicile (texte + couleur, jamais la couleur seule).
     groupe : abonnements | paiements | collectes  (config/cleancomores.php) --}}
@props(['groupe', 'valeur', 'taille' => 'md'])
@php
    $e = config("cleancomores.$groupe.$valeur", ['label' => ucfirst((string) $valeur), 'classes' => 'bg-zinc-100 text-zinc-800 ring-zinc-300']);
    $tailles = ['sm' => 'px-2 py-0.5 text-xs gap-1', 'md' => 'px-2.5 py-1 text-sm gap-1.5', 'lg' => 'px-3 py-1.5 text-base gap-2'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full font-medium ring-1 ring-inset '.$e['classes'].' '.$tailles[$taille]]) }}>
    @isset($e['icone'])<x-cc.icon :name="$e['icone']" class="{{ $taille === 'lg' ? 'size-5' : 'size-4' }}" />@endisset
    <span>{{ $e['label'] }}</span>
</span>
