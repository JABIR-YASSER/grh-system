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
use Filament\Support\Enums\Alignment; // 👈 Import obligatoire pour l'alignement comptable

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
                                    $salaireBase = $contrat->salaire;
                                    
                                    // 1. Récupérer les Primes non payées (Gains)
                                    $totalPrimes = \App\Models\Prime::where('employe_id', $state)
                                        ->where('payee', false)
                                        ->sum('montant');

                                    // 2. Calculer les Absences via Pointages (Retenues)
                                    $joursTravailles = \App\Models\Pointage::where('employe_id', $state)
                                        ->whereMonth('date', now()->subMonth()->month)
                                        ->count();
                                        
                                    // Sur une base de 26 jours ouvrables au Maroc
                                    $joursAbsents = max(0, 26 - $joursTravailles);
                                    $retenueAbsence = ($salaireBase / 26) * $joursAbsents;

                                    // 3. Calcul de la base Imposable
                                    $brutTotal = $salaireBase + $totalPrimes; 
                                    $baseImposable = max(0, $brutTotal - $retenueAbsence); 

                                    // 4. Calculs Fiscaux (CNSS & AMO)
                                    $baseCnss = min($baseImposable, 6000);
                                    $cnss = $baseCnss * 0.0448;
                                    $amo = $baseImposable * 0.0226;
                                    
                                    // 5. Totaux Finaux
                                    $totalDeductions = $retenueAbsence + $cnss + $amo;
                                    $net = max(0, $brutTotal - $totalDeductions);

                                    // 6. Mise à jour des champs Filament
                                    $set('salaire_brut', number_format($brutTotal, 2, '.', ''));
                                    $set('deductions', number_format($totalDeductions, 2, '.', ''));
                                    $set('net_a_payer', number_format($net, 2, '.', ''));
                                    
                                    // On sauvegarde tous les détails pour le PDF
                                    $set('donnees_calcul', [
                                        'salaire_base' => $salaireBase,
                                        'total_primes' => $totalPrimes,
                                        'jours_absents' => $joursAbsents,
                                        'retenue_absence' => round($retenueAbsence, 2),
                                        'base_cnss' => round($baseCnss, 2),
                                        'montant_cnss' => round($cnss, 2),
                                        'montant_amo' => round($amo, 2),
                                    ]);
                                }
                            }),

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
                            ->label('Salaire Brut (Base + Primes)')
                            ->prefix('DH')
                            ->readOnly(),

                        TextInput::make('deductions')
                            ->label('Retenues (Absences + CNSS + AMO)')
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
                // 👇 OPTIMISATION 1 : Nom et Prénom fusionnés
                
                TextColumn::make('employe.user.nom')
                    ->label('Employé')
                    ->formatStateUsing(fn ($record) => $record->employe->user->nom . ' ' . $record->employe->user->prenom)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employe.user', function (Builder $q) use ($search) {
                            $q->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('mois')
                    ->label('Mois')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('annee')
                    ->label('Année')
                    ->searchable()
                    ->sortable(),

                // 👇 OPTIMISATION 2 : Alignement Comptable (à droite)
                TextColumn::make('salaire_brut')
                    ->money('mad')
                    ->label('Brut')
                    ->alignment(Alignment::End)
                    ->sortable(),

                // 👇 OPTIMISATION 2 : Alignement Comptable (à droite)
                TextColumn::make('net_a_payer')
                    ->money('mad')
                    ->weight('bold')
                    ->color('success')
                    ->label('Net à Payer')
                    ->alignment(Alignment::End)
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
            // J'ai retiré le SelectFilter de statut d'ici, car tes Onglets font déjà ce travail !
            ->filters([
                // Tu peux ajouter d'autres filtres plus tard (ex: par département)
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('Bulletin')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (Paie $record) => route('paie.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                
                // 👇 OPTIMISATION 3 : Sécurité, on ne supprime pas une fiche payée
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->statut === 'paye'),
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