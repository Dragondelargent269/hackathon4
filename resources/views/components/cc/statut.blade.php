{{-- Badge de statut : toujours texte + icône + couleur (jamais la couleur seule).
     Accepte une chaîne ('en_cours') ou un enum PHP adossé (->value). --}}
@props(['statut', 'taille' => 'md'])
@php
    $cle = $statut instanceof \BackedEnum ? $statut->value : (string) $statut;
    $s = config("cleancomores.statuts.$cle", ['label' => ucfirst($cle), 'classes' => 'bg-zinc-100 text-zinc-800 ring-zinc-300', 'icone' => 'dots']);
    $tailles = ['sm' => 'px-2 py-0.5 text-xs gap-1', 'md' => 'px-2.5 py-1 text-sm gap-1.5', 'lg' => 'px-3 py-1.5 text-base gap-2'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center whitespace-nowrap rounded-full font-medium ring-1 ring-inset '.$s['classes'].' '.$tailles[$taille]]) }}>
    <x-cc.icon :name="$s['icone']" class="{{ $taille === 'lg' ? 'size-5' : 'size-4' }}" />
    <span><span class="sr-only">Statut : </span>{{ $s['label'] }}</span>
</span>
