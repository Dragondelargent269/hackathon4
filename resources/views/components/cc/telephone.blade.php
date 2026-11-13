{{--
    Champ téléphone comorien, à placer dans <x-cc.champ pour="…">.
    Saisie limitée aux chiffres, espaces, points, tirets et « + » en tête (resources/js/app.js, [data-telephone]) ;
    contrôle du format à la sortie du champ. La validation qui fait foi est côté serveur (App\Rules\TelephoneComores).
--}}
@props(['name' => 'telephone', 'id' => null, 'value' => null, 'requis' => false, 'aide' => true])
@php
    $id ??= $name;
    $aDesErreurs = $errors->has($name);
    // Relie le champ au texte d'aide et au message d'erreur affichés par <x-cc.champ>
    $decrit = trim(($aide ? "$id-aide " : '').($aDesErreurs ? "$id-erreur" : ''));
@endphp
<input
    id="{{ $id }}" name="{{ $name }}" type="tel" inputmode="tel" autocomplete="tel"
    maxlength="{{ \App\Support\Telephone::LONGUEUR_MAX }}" pattern="[+]?[0-9 .\-]{7,17}"
    placeholder="+269 321 00 11" value="{{ $value }}" data-telephone
    title="Numéro comorien : 7 chiffres commençant par 3, 4 ou 7, avec ou sans +269"
    @if ($requis) required @endif
    @if ($aDesErreurs) aria-invalid="true" @endif
    @if ($decrit) aria-describedby="{{ $decrit }}" @endif
    {{ $attributes->merge(['class' => 'cc-input tabular-nums']) }}
>
<p id="{{ $id }}-format" class="flex items-center gap-1 text-sm font-medium text-red-700" data-telephone-erreur hidden></p>
