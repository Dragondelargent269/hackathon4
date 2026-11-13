<?php

namespace App\Http\Controllers\Citoyen;

use App\Http\Controllers\Controller;
use App\Rules\TelephoneComores;
use App\Support\Telephone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function edit(Request $request): View
    {
        return view('citoyen.profil', [
            'utilisateur' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $zones = config('cleancomores.zones');

        $request->merge([
            'telephone' => Telephone::normaliser($request->input('telephone')),
            'email' => trim((string) $request->input('email')) ?: null,
        ]);

        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:telephone', 'email', 'max:191', Rule::unique('users')->ignore($user->id)],
            'telephone' => ['nullable', 'required_without:email', new TelephoneComores, Rule::unique('users')->ignore($user->id)],
            'commune' => ['required', Rule::in(collect($zones)->pluck('communes')->flatten()->all())],
        ]);

        $user->update($donnees);
        $user->forceFill([
            'zone' => collect($zones)->search(fn ($z) => in_array($donnees['commune'], $z['communes'], true)),
        ])->save();

        return back()->with('success', 'Votre profil a été mis à jour.');
    }

    public function notifications(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'notif_email' => ['required', 'boolean'],
            'notif_changement_statut' => ['required', 'boolean'],
            'notif_resolution' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill($donnees)->save();

        return back()->with('success', 'Vos préférences de notification ont été enregistrées.');
    }

    public function motDePasse(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $donnees['password']]);

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }
}
