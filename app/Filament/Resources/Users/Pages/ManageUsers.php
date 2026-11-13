<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\Telephone;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(fn (array $data) => self::enregistrer(new User, $data)),
        ];
    }

    /** Enregistre aussi le rôle (non mass-assignable) ; le mot de passe est haché par le cast du modèle. */
    public static function enregistrer(User $user, array $data): User
    {
        $data = collect($data)->only(['name', 'role', 'email', 'telephone', 'commune', 'password']);
        if ($data->has('telephone')) {
            $data['telephone'] = Telephone::normaliser($data['telephone']);
        }
        // L'île (zone) se déduit de la commune, comme à l'inscription
        if ($data->has('commune')) {
            $data['zone'] = collect(config('cleancomores.zones'))
                ->search(fn ($z) => in_array($data['commune'], $z['communes'], true)) ?: null;
        }
        $user->forceFill($data->all());
        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
