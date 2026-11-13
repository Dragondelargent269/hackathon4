<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

/**
 * Comptes et rôles (citoyen, agent, responsable) attribués par l'organisation.
 * « role » n'est pas mass-assignable dans User : il est enregistré par
 * ManageUsers via forceFill(), uniquement depuis ce panneau réservé aux responsables.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?int $navigationSort = 3;

    public const ROLES = [
        User::ROLE_CITOYEN => 'Citoyen',
        User::ROLE_AGENT => 'Agent de terrain',
        User::ROLE_RESPONSABLE => 'Responsable',
    ];

    public static function form(Schema $schema): Schema
    {
        $communes = collect(config('cleancomores.zones'))->pluck('communes')->flatten()->mapWithKeys(fn ($c) => [$c => $c])->all();

        return $schema
            ->components([
                TextInput::make('name')->label('Nom complet')->required()->maxLength(191),
                Select::make('role')->label('Rôle')->required()->options(self::ROLES)->default(User::ROLE_AGENT)
                    // Un responsable ne peut pas retirer son propre rôle (il perdrait l'accès)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                TextInput::make('email')->label('E-mail')->email()->maxLength(191)
                    ->unique(ignoreRecord: true)->requiredWithout('telephone'),
                TextInput::make('telephone')->label('Téléphone')->tel()->regex('/^\+?[0-9]{7,15}$/')
                    ->unique(ignoreRecord: true)->requiredWithout('email'),
                Select::make('commune')->label('Commune')->options($communes)->searchable(),
                TextInput::make('password')->label('Mot de passe')->password()->revealable()
                    ->rule(Password::default())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText('Laisser vide pour ne pas le changer.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable(),
                TextColumn::make('role')->label('Rôle')->badge()
                    ->formatStateUsing(fn (string $state) => self::ROLES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        User::ROLE_RESPONSABLE => 'danger',
                        User::ROLE_AGENT => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('email')->label('E-mail')->searchable(),
                TextColumn::make('telephone')->label('Téléphone')->searchable(),
                TextColumn::make('commune')->label('Commune')->sortable(),
                TextColumn::make('created_at')->label('Inscrit le')->date('d/m/Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Rôle')->options(self::ROLES),
            ])
            ->recordActions([
                EditAction::make()->using(fn (User $record, array $data) => ManageUsers::enregistrer($record, $data)),
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
