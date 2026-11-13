<?php

namespace App\Filament\Resources\PointCollectes;

use App\Filament\Resources\PointCollectes\Pages\ManagePointCollectes;
use App\Models\PointCollecte;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Points de dépôt / centres de collecte affichés sur les cartes. */
class PointCollecteResource extends Resource
{
    protected static ?string $model = PointCollecte::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $modelLabel = 'point de collecte';

    protected static ?string $pluralModelLabel = 'points de collecte';

    protected static ?string $navigationLabel = 'Points de collecte';

    protected static ?int $navigationSort = 2;

    /** Commune => zone (île), depuis config/cleancomores.php */
    private static function communes(): array
    {
        return collect(config('cleancomores.zones'))
            ->mapWithKeys(fn ($z, $cle) => collect($z['communes'])->mapWithKeys(fn ($c) => [$c => $cle])->all())
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nom')->label('Nom')->required()->maxLength(150),
                Select::make('commune')->label('Commune')->required()
                    ->options(collect(self::communes())->keys()->mapWithKeys(fn ($c) => [$c => $c])->all())
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('zone', self::communes()[$state] ?? null)),
                Select::make('zone')->label('Île')->required()
                    ->options(collect(config('cleancomores.zones'))->map(fn ($z) => $z['label'])->all()),
                TextInput::make('adresse')->label('Adresse')->maxLength(255),
                TextInput::make('latitude')->label('Latitude')->required()->numeric()->minValue(-12.5)->maxValue(-11.3)->helperText('Ex. : -11.7045'),
                TextInput::make('longitude')->label('Longitude')->required()->numeric()->minValue(43.1)->maxValue(44.6)->helperText('Ex. : 43.2573'),
                TextInput::make('horaires')->label('Horaires')->maxLength(150),
                TextInput::make('dechets_acceptes')->label('Déchets acceptés')->maxLength(255),
                Toggle::make('actif')->label('Affiché sur la carte')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable(),
                TextColumn::make('commune')->label('Commune')->searchable()->sortable(),
                TextColumn::make('zone')->label('Île')->formatStateUsing(fn ($state) => config("cleancomores.zones.$state.label", $state)),
                TextColumn::make('horaires')->label('Horaires'),
                IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('zone')->label('Île')->options(collect(config('cleancomores.zones'))->map(fn ($z) => $z['label'])->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePointCollectes::route('/'),
        ];
    }
}
