<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        // Normalisation : "+269 321 00 11" devient "+2693210011" ; un champ vide devient null
        $input['telephone'] = preg_replace('/\s+/', '', (string) ($input['telephone'] ?? '')) ?: null;
        $input['email'] = trim((string) ($input['email'] ?? '')) ?: null;

        $zones = config('cleancomores.zones');
        $communes = collect($zones)->pluck('communes')->flatten()->all();

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:telephone', 'email', 'max:191', Rule::unique(User::class)],
            'telephone' => ['nullable', 'required_without:email', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique(User::class)],
            'commune' => ['required', Rule::in($communes)],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'conditions' => ['accepted'],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'telephone' => $input['telephone'],
            'commune' => $input['commune'],
            'password' => $input['password'],
        ]);

        // Rôle : jamais choisi par le formulaire → valeur par défaut 'citoyen' en base
        $user->forceFill([
            'zone' => collect($zones)->search(fn ($z) => in_array($input['commune'], $z['communes'], true)),
        ])->save();

        return $user;
    }
}