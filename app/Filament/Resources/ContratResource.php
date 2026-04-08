<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContratResource\Pages;
use App\Models\Contrat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class ContratResource extends Resource
{
    protected static ?string $model = Contrat::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Gestion RH';

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        // On vérifie s'il y a un utilisateur connecté ET s'il est admin
        return $user && $user->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Détails du Contrat')
                    ->schema([
                        // Recherche par Matricule (C'est le plus simple pour l'instant)
                        Select::make('employe_id')
                            ->relationship('employe', 'matricule')
                            ->label('Employé (Matricule)')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('type')
                            ->options([
                                'CDI' => 'CDI (Durée Indéterminée)',
                                'CDD' => 'CDD (Durée Déterminée)',
                                'ANAPEC' => 'Contrat ANAPEC',
                                'STAGE' => 'Convention de Stage',
                            ])
                            ->required()
                            ->native(false),

                        TextInput::make('salaire')
                            ->label('Salaire Mensuel Net')
                            ->numeric()
                            ->prefix('DH') // Ajoute la devise
                            ->required(),

                    ])->columns(3),

                Section::make('Période')
                    ->schema([
                        DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('date_fin')
                            ->label('Date de fin')
                            ->helperText('Laisser vide pour un CDI')
                            ->displayFormat('d/m/Y'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // On affiche le matricule + le nom (via la relation employe.user)
                TextColumn::make('employe.matricule')
                    ->label('Matricule')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                // 👇 Optimisation : Rendu triable 👇
                TextColumn::make('employe.user.nom')
                    ->label('Nom Employé')
                    ->searchable()
                    ->sortable(),

                // 👇 Optimisation : Rendu recherchable et triable 👇
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'CDI' => 'success',
                        'CDD' => 'warning',
                        'ANAPEC' => 'info',
                        'STAGE' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salaire')
                    ->money('mad') // Formattage monétaire automatique
                    ->sortable(),

                // 👇 Optimisation : Dates triables 👇
                TextColumn::make('date_debut')
                    ->date('d/m/Y')
                    ->label('Début')
                    ->sortable(),

                // 👇 Optimisation : Dates triables 👇
                TextColumn::make('date_fin')
                    ->date('d/m/Y')
                    ->label('Fin')
                    ->placeholder('Indéterminé') // Si c'est vide (CDI)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'CDI' => 'CDI',
                        'CDD' => 'CDD',
                        'ANAPEC' => 'ANAPEC',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // 👇 Optimisation : Tri par défaut (les plus récents en premier) 👇
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContrats::route('/'),
            'create' => Pages\CreateContrat::route('/create'),
            'edit' => Pages\EditContrat::route('/{record}/edit'),
        ];
    }
}