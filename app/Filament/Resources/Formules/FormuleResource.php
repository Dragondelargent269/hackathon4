<?php

namespace App\Filament\Resources\Formules;

use App\Filament\Resources\Formules\Pages\ManageFormules;
use App\Models\Formule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Formules de collecte à domicile : prix, durée, collectes et services
 * se modifient ici, sans toucher au code. Les abonnements déjà vendus
 * gardent une copie de leur formule (le changement ne s'applique qu'aux nouveaux).
 */
class FormuleResource extends Resource
{
    protected static ?string $model = Formule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $modelLabel = 'formule';

    protected static ?string $pluralModelLabel = 'formules';

    protected static ?string $navigationLabel = 'Formules de collecte';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nom')->label('Nom')->required()->maxLength(100),
                TextInput::make('slug')->label('Identifiant dans l\'URL')->required()->maxLength(100)->alphaDash()
                    ->unique(ignoreRecord: true)->helperText('Ex. : mensuelle'),
                Textarea::make('description')->label('Description')->columnSpanFull(),
                TextInput::make('prix')->label('Prix')->required()->integer()->minValue(0)->suffix('KMF'),
                TextInput::make('duree_jours')->label('Durée de validité')->required()->integer()->minValue(1)->suffix('jours'),
                TextInput::make('collectes_incluses')->label('Collectes incluses')->required()->integer()->minValue(1),
                TextInput::make('ordre')->label('Ordre d\'affichage')->required()->integer()->default(0),
                TagsInput::make('services')->label('Services inclus')->placeholder('Ajouter un service puis Entrée')->columnSpanFull(),
                Toggle::make('active')->label('Proposée aux citoyens')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ordre')
            ->columns([
                TextColumn::make('nom')->label('Nom')->searchable(),
                TextColumn::make('prix')->label('Prix')->numeric(thousandsSeparator: ' ')->suffix(' KMF')->sortable(),
                TextColumn::make('duree_jours')->label('Durée')->suffix(' j')->sortable(),
                TextColumn::make('collectes_incluses')->label('Collectes')->sortable(),
                TextColumn::make('abonnements_count')->label('Abonnements')->counts('abonnements'),
                IconColumn::make('active')->label('Active')->boolean(),
                TextColumn::make('ordre')->label('Ordre')->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                // Une formule déjà vendue se désactive, elle ne se supprime pas
                DeleteAction::make()->hidden(fn (Formule $record) => $record->abonnements()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFormules::route('/'),
        ];
    }
}
