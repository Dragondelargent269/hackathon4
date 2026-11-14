<?php

namespace App\Http\Controllers\Citoyen;

use App\Enums\Categorie;
use App\Http\Controllers\Controller;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SignalementController extends Controller
{
    public function index(Request $request): View
    {
        return view('citoyen.signalements.index', [
            'utilisateur' => $request->user(),
            'signalements' => $request->user()->signalements()
                ->with('agent')
                ->latest('updated_at')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        return view('citoyen.signalements.create', [
            'utilisateur' => $request->user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $zones = config('cleancomores.zones');
        $communes = collect($zones)->pluck('communes')->flatten()->all();

        $donnees = $request->validate([
            'categorie' => ['required', Rule::enum(Categorie::class)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'commune' => ['required', 'string', Rule::in($communes)],
            'repere' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-12.5,-11.3'],
            'longitude' => ['nullable', 'numeric', 'between:43.1,44.6'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        // La zone (île) est déduite de la commune, jamais envoyée par le formulaire
        $donnees['zone'] = collect($zones)
            ->search(fn ($zone) => in_array($donnees['commune'], $zone['communes'], true));

        $donnees['repere'] = $donnees['repere'] ?? '';

        // La photo est rangée dans storage/app/public/signalements
        $donnees['photo'] = $request->hasFile('photo')
            ? $request->file('photo')->store('signalements', 'public')
            : null;

        // user_id est rempli automatiquement par la relation
        $signalement = $request->user()->signalements()->create($donnees);

        return redirect()
            ->route('citoyen.signalements.show', $signalement)
            ->with('success', "Merci ! Votre signalement {$signalement->reference} a bien été envoyé. Vous serez informé(e) de chaque étape.");
    }

    public function show(Request $request, Signalement $signalement): View
    {
        // Un citoyen ne voit QUE ses propres signalements
        abort_unless($signalement->user_id === $request->user()->id, 404);

        $signalement->load(['agent', 'historique.user']);

        return view('citoyen.signalements.show', [
            'utilisateur' => $request->user(),
            'signalement' => $signalement,
        ]);
    }
}