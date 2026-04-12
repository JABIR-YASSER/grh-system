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
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Changement de l'icône pour quelque chose de plus explicite
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Système'; // Groupé avec la config

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
                            // On ne demande le mot de passe que lors de la création
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            // Hachage automatique du mot de passe avant enregistrement
                            ->dehydrated(fn ($state) => filled($state))
                            ->mutateDehydratedStateUsing(fn ($state) => Hash::make($state)),
                    ])->columns(2),

                Forms\Components\Section::make('Paramètres du compte')
                    ->schema([
                        TextInput::make('telephone')
                            ->tel()
                            ->maxLength(255),
                        Select::make('etat')
                            ->options([
                                'actif' => 'Actif',
                                'bloque' => 'Bloqué',
                            ])
                            ->default('actif')
                            ->required(),
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
                TextColumn::make('nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('prenom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
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
                TextColumn::make('derniere_connexion_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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