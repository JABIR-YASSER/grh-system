<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeResource\Pages;
use App\Models\Employe;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class EmployeResource extends Resource
{
    protected static ?string $model = Employe::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Gestion RH';
    
    protected static ?string $recordTitleAttribute = 'matricule';

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        return $user && $user->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations Personnelles')
                    ->description('Lier cet employé à un compte utilisateur existant.')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'email')
                            ->label('Compte Utilisateur')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('nom')->required(),
                                TextInput::make('prenom')->required(),
                                TextInput::make('email')->email()->required(),
                                TextInput::make('password')->password()->required(),
                            ]),
                        ToggleButtons::make('etat_compte') // On lui donne un nom imaginaire pour éviter l'erreur SQL "user"
                            ->label('Statut du compte')
                            ->options([
                                'actif' => 'Actif (En poste)',
                                'inactif' => 'Inactif (Archivé)',
                            ])
                            ->colors([
                                'actif' => 'success',
                                'inactif' => 'danger',
                            ])
                            ->icons([
                                'actif' => 'heroicon-m-check-circle',
                                'inactif' => 'heroicon-m-x-circle',
                            ])
                            ->inline()
                            // 1. On va chercher la valeur dans la table Users
                            ->formatStateUsing(function ($record) {
                                return $record?->user?->etat;
                            })
                            // 2. On empêche Filament d'essayer de sauver "etat_compte" dans la table employes
                            ->dehydrated(false) 
                            // 3. On sauvegarde manuellement dans la table Users après validation
                            ->afterStateUpdated(function ($record, $state) {
                                $record->user->update(['etat' => $state]);
                            })
                            ->live() // Permet de déclencher afterStateUpdated instantanément
                            ->required(),

                        TextInput::make('matricule')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Affectation & Poste')
                    ->schema([
                        Select::make('departement_id')
                            ->relationship('departement', 'libelle')
                            ->label('Département')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('code')->required()->label('Code (ex: RH)'),
                                TextInput::make('libelle')->required(),
                            ]),

                        Select::make('poste_id')
                            ->relationship('poste', 'titre')
                            ->label('Poste Occupé')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('titre')->required(),
                                TextInput::make('description'),
                            ]),

                        DatePicker::make('date_embauche')
                            ->label('Date d\'embauche')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.prenom')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(), // 👇 Optimisation : Rendu triable

                TextColumn::make('matricule')
                    ->badge()
                    ->searchable()
                    ->sortable(), // 👇 Optimisation : Rendu triable

                TextColumn::make('departement.libelle')
                    ->label('Département')
                    ->searchable() // 👇 Optimisation : Recherchable
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('poste.titre')
                    ->label('Poste')
                    ->searchable() // 👇 Optimisation : Recherchable
                    ->sortable(),

                TextColumn::make('date_embauche')
                    ->date('d/m/Y')
                    ->sortable(),
                
                TextColumn::make('user.etat')
                    ->label('État')
                    ->badge() // Transforme le texte en badge coloré
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'inactif' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('departement')
                    ->relationship('departement', 'libelle')
                    ->label('Département'),
                
                // 👇 Nouveau filtre par Poste 👇
                SelectFilter::make('poste')
                    ->relationship('poste', 'titre')
                    ->label('Poste'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('archiver')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->user->etat === 'inactif') // Cache le bouton si déjà archivé
                    ->action(function ($record) {
                        $record->user->update(['etat' => 'inactif']);
                        
                        Notification::make()
                            ->title('Employé archivé avec succès')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // 👇 Tri par défaut : les derniers arrivés en haut 👇
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployes::route('/'),
            'create' => Pages\CreateEmploye::route('/create'),
            'edit' => Pages\EditEmploye::route('/{record}/edit'),
        ];
    }
}