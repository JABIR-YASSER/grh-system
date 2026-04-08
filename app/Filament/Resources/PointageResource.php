<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointageResource\Pages;
use App\Models\Pointage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PointageResource extends Resource
{
    protected static ?string $model = Pointage::class;

    // 1. Icône plus logique et groupe de navigation
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $navigationLabel = 'Pointages';

    public static function canViewAny(): bool
    {
        // Seuls les admins peuvent voir le menu et la liste des pointages
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        // 2. Formulaire complété pour permettre aux admins de corriger les pointages
        return $form
            ->schema([
                Forms\Components\Select::make('employe_id')
                    ->relationship('employe', 'matricule') // Tu peux aussi concaténer Nom + Matricule si tu as une fonction dans le modèle
                    ->label('Employé')
                    ->searchable()
                    ->required(),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->default(now())
                    ->required(),
                    
                Forms\Components\TimePicker::make('heure_arrivee')
                    ->label('Heure d\'arrivée')
                    ->required(),
                    
                Forms\Components\TimePicker::make('heure_depart')
                    ->label('Heure de départ'), // Pas required car l'employé n'a peut-être pas encore terminé sa journée
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employe.user.nom') // Ajout du nom pour plus de clarté
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employe.matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y') // Formatage de la date à la française
                    ->sortable()
                    ->label('Date'),

                Tables\Columns\TextColumn::make('heure_arrivee')
                    ->time('H:i')
                    ->label('Arrivée')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('heure_depart')
                    ->time('H:i')
                    ->label('Départ')
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                // 3. Filtre de date corrigé avec la logique SQL
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_debut')->label('Du'),
                        Forms\Components\DatePicker::make('date_fin')->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_debut'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_fin'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc'); // Affiche les pointages les plus récents en premier
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
            'index' => Pages\ListPointages::route('/'),
            'create' => Pages\CreatePointage::route('/create'),
            'edit' => Pages\EditPointage::route('/{record}/edit'),
        ];
    }
}