{{-- Champ de formulaire : label + contrôle (slot) + aide + erreur de validation Laravel --}}
@props(['label', 'pour', 'aide' => null, 'requis' => false, 'erreur' => null])
@php $cleErreur = $erreur ?? $pour; @endphp
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <label for="{{ $pour }}" class="block text-sm font-medium text-zinc-900">
        {{ $label }}
        @if ($requis)
            <span class="text-red-700" aria-hidden="true">*</span><span class="sr-only">(obligatoire)</span>
        @else
            <span class="font-normal text-zinc-500">(facultatif)</span>
        @endif
    </label>
    {{ $slot }}
    @if ($aide)<p id="{{ $pour }}-aide" class="text-sm text-zinc-600">{{ $aide }}</p>@endif
    @error($cleErreur)
        <p id="{{ $pour }}-erreur" class="flex items-center gap-1 text-sm font-medium text-red-700"><x-cc.icon name="alert" class="size-4" />{{ $message }}</p>
    @enderror
</div>
