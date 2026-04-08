<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaieResource\Pages;
use App\Models\Paie;
use App\Models\Employe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class PaieResource extends Resource
{
    protected static ?string $model = Paie::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Gestion Paie';
    protected static ?string $navigationLabel = 'Bulletins de Paie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Calcul Automatique')
                    ->description('Choisissez un employé, le système calcule le reste.')
                    ->schema([
                        Select::make('employe_id')
                            ->label('Employé')
                            ->options(Employe::with('user')->get()->pluck('user.nom', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if (!$state) return;
                                
                                $employe = Employe::find($state);
                                $contrat = $employe?->contratActuel()->first();

                                if ($contrat) {
                                    $brut = $contrat->salaire;
                                    
                                    $baseCnss = min($brut, 6000);
                                    $cnss = $baseCnss * 0.0448;
                                    $amo = $brut * 0.0226;
                                    
                                    $totalDeductions = $cnss + $amo;
                                    $net = $brut - $totalDeductions;

                                    $set('salaire_brut', number_format($brut, 2, '.', ''));
                                    $set('deductions', number_format($totalDeductions, 2, '.', ''));
                                    $set('net_a_payer', number_format($net, 2, '.', ''));
                                    
                                    $set('donnees_calcul', [
                                        'base_cnss' => $baseCnss,
                                        'taux_cnss' => 4.48,
                                        'montant_cnss' => $cnss,
                                        'montant_amo' => $amo,
                                    ]);
                                }
                            }),

                        // 👇 Champ fixe et verrouillé sur le mois précédent 👇
                        TextInput::make('mois')
                            ->label('Mois de référence')
                            ->default(function () {
                                $nomsMois = [
                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                                ];
                                return $nomsMois[now()->subMonth()->month];
                            })
                            ->readOnly()
                            ->required(),
                            
                        // 👇 Champ fixe et verrouillé sur l'année correspondante 👇
                        TextInput::make('annee')
                            ->label('Année')
                            ->default(now()->subMonth()->year)
                            ->readOnly()
                            ->required(),
                            
                        Select::make('statut')
                            ->options([
                                'en_attente' => 'En attente',
                                'paye' => 'Payé',
                            ])
                            ->default('en_attente')
                            ->required(),

                    ])->columns(2),

                Section::make('Détails Financiers')
                    ->schema([
                        TextInput::make('salaire_brut')
                            ->label('Salaire Brut')
                            ->prefix('DH')
                            ->readOnly(),

                        TextInput::make('deductions')
                            ->label('Retenues (CNSS+AMO)')
                            ->prefix('- DH')
                            ->readOnly(),

                        TextInput::make('net_a_payer')
                            ->label('Net à Payer')
                            ->prefix('= DH')
                            ->extraInputAttributes(['style' => 'font-weight: bold; color: green; font-size: 1.1em'])
                            ->readOnly(),
                            
                        KeyValue::make('donnees_calcul')
                            ->label('Détails techniques (JSON)')
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.user.nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('mois')
                    ->label('Mois')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('annee')
                    ->label('Année')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('salaire_brut')
                    ->money('mad')
                    ->label('Brut')
                    ->sortable(),

                TextColumn::make('net_a_payer')
                    ->money('mad')
                    ->weight('bold')
                    ->color('success')
                    ->label('Net à Payer')
                    ->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paye' => 'success',
                        'en_attente' => 'warning',
                        'annule' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options([
                        'en_attente' => 'En attente',
                        'paye' => 'Payé',
                    ])
                    ->label('Filtrer par statut'),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('Bulletin')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Paie $record) => route('paie.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && !$user->hasRole('admin')) {
            $query->whereHas('employe', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user ? $user->hasRole('admin') : false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user ? $user->hasRole('admin') : false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user ? $user->hasRole('admin') : false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaies::route('/'),
            'create' => Pages\CreatePaie::route('/create'),
            'edit' => Pages\EditPaie::route('/{record}/edit'),
        ];
    }
}