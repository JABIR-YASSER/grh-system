<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrimeResource\Pages;
use App\Models\Prime;
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
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class PrimeResource extends Resource
{
    protected static ?string $model = Prime::class;

    // Changement de l'icône pour correspondre au thème de l'argent/récompense
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Gestion Paie';
    protected static ?string $navigationLabel = 'Primes & Bonus';

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
                Section::make('Détails de la Prime')
                    ->schema([
                        Select::make('employe_id')
                            ->label('Employé')
                            // On récupère le nom depuis la relation User pour que ce soit lisible
                            ->options(Employe::with('user')->get()->pluck('user.nom', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('type')
                            ->label('Type de prime')
                            ->options([
                                'Rendement' => 'Prime de Rendement',
                                'Aid' => 'Prime de l\'Aïd',
                                'Anciennete' => 'Prime d\'Ancienneté',
                                '13eme_mois' => '13ème Mois',
                                'Exceptionnelle' => 'Prime Exceptionnelle',
                            ])
                            ->searchable()
                            ->required(),

                        TextInput::make('montant')
                            ->label('Montant de la prime')
                            ->numeric()
                            ->prefix('DH')
                            ->required(),

                        DatePicker::make('date')
                            ->label('Date d\'attribution')
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->required(),

                        Toggle::make('payee')
                            ->label('Prime payée / incluse dans la paie ?')
                            ->helperText('Si désactivé, cette prime sera automatiquement ajoutée au prochain bulletin de paie de l\'employé.')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.user.nom')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('montant')
                    ->label('Montant')
                    ->money('mad')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date d\'attribution')
                    ->date('d/m/Y')
                    ->sortable(),

                IconColumn::make('payee')
                    ->label('Payée ?')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->filters([
                // Un filtre super pratique pour trouver les primes en attente de paiement
                TernaryFilter::make('payee')
                    ->label('Statut de paiement')
                    ->placeholder('Toutes les primes')
                    ->trueLabel('Primes payées')
                    ->falseLabel('Primes en attente'),
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
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListPrimes::route('/'),
            'create' => Pages\CreatePrime::route('/create'),
            'edit' => Pages\EditPrime::route('/{record}/edit'),
        ];
    }
}