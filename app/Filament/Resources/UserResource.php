<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons; // 👈 Import ajouté
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // 👈 Import ajouté pour la sécurité

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Système';

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de connexion')
                    ->schema([
                        TextInput::make('nom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('prenom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->mutateDehydratedStateUsing(fn ($state) => Hash::make($state)),
                    ])->columns(2),

                Forms\Components\Section::make('Paramètres du compte')
                    ->schema([
                        TextInput::make('telephone')
                            ->tel()
                            ->maxLength(255),
                            
                        // 👇 OPTIMISATION 1 & 2 : ToggleButtons + Sécurité
                        ToggleButtons::make('etat')
                            ->label('État du compte')
                            ->options([
                                'actif' => 'Actif',
                                'bloque' => 'Bloqué',
                            ])
                            ->colors([
                                'actif' => 'success',
                                'bloque' => 'danger',
                            ])
                            ->icons([
                                'actif' => 'heroicon-m-check-circle',
                                'bloque' => 'heroicon-m-x-circle',
                            ])
                            ->inline()
                            ->default('actif')
                            ->required()
                            // L'utilisateur connecté ne peut pas se bloquer lui-même
                            ->disabled(fn ($record) => $record && $record->id === Auth::id()),

                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Rôles / Permissions'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Fusion Nom et Prénom pour gagner de la place
                TextColumn::make('nom')
                    ->label('Utilisateur')
                    ->formatStateUsing(fn ($record) => $record->nom . ' ' . $record->prenom)
                    ->searchable(['nom', 'prenom'])
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope'), // Petite icône sympa

                TextColumn::make('telephone')
                    ->searchable(),

                TextColumn::make('etat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'bloque' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('roles.name')
                    ->badge()
                    ->color('info')
                    ->label('Rôle'),

                // 👇 OPTIMISATION 3 : Format "Il y a X temps"
                TextColumn::make('derniere_connexion_at')
                    ->label('Dernière connexion')
                    ->since() // Transforme la date en texte relatif
                    ->sortable(),
            ])
            ->filters([
                // Filtres gérés par les onglets
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // 👇 OPTIMISATION 1 : Sécurité anti-suicide numérique
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->id === Auth::id())
                    ->tooltip("Vous ne pouvez pas supprimer votre propre compte."),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}