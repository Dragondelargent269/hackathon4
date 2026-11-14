{{-- Messages de session. Côté back : return back()->with('success', '…') ou ->with('error', '…') --}}
<div {{ $attributes->merge(['class' => 'space-y-2']) }} aria-live="polite">
    @if (session('success'))<x-cc.alerte type="succes">{{ session('success') }}</x-cc.alerte>@endif
    @if (session('error'))<x-cc.alerte type="erreur">{{ session('error') }}</x-cc.alerte>@endif
    @if ($errors->any())
        <x-cc.alerte type="erreur" titre="Certaines informations sont à corriger">
            <ul class="list-disc ps-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </x-cc.alerte>
    @endif
</div>
