<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CongeResource\Pages;
use App\Models\Conge;
use App\Models\DossierEmploye; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification; 
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon; // 👇 Ajout de Carbon pour gérer les dates

class CongeResource extends Resource
{
    protected static ?string $model = Conge::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $navigationLabel = 'Congés & Absences';

    public static function getNavigationBadge(): ?string
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && $user->hasRole('admin')) {
            $compteur = static::getModel()::where('statut', 'en_attente')->count();
            return $compteur > 0 ? (string) $compteur : null;
        }

        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; 
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && !$user->hasRole('admin') && $record->statut !== 'en_attente') {
            return false;
        }

        return true;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && !$user->hasRole('admin') && $record->statut !== 'en_attente') {
            return false;
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Demande')
                    ->schema([
                        Select::make('employe_id')
                            ->relationship('employe', 'matricule')
                            ->label('Employé')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(function () {
                                /** @var \App\Models\User|null $user */
                                $user = \Illuminate\Support\Facades\Auth::user();
                                
                                if ($user && !$user->hasRole('admin')) {
                                    return \App\Models\Employe::where('user_id', $user->id)->value('id');
                                }
                                return null;
                            })
                            ->disabled(function () {
                                /** @var \App\Models\User|null $user */
                                $user = \Illuminate\Support\Facades\Auth::user();
                                return $user && !$user->hasRole('admin');
                            })
                            ->dehydrated(),

                        Select::make('type')
                            ->options([
                                'paye' => 'Congé Payé',
                                'maladie' => 'Maladie',
                                'sans_solde' => 'Sans Solde',
                                'maternite' => 'Maternité',
                                'recuperation' => 'Récupération',
                            ])
                            ->required(),

                        DatePicker::make('date_debut')->required(),
                        DatePicker::make('date_fin')->required()
                            ->afterOrEqual('date_debut'), 
                            
                        Textarea::make('motif')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Validation Administration')
                    ->schema([
                        Select::make('statut')
                            ->options([
                                'en_attente' => 'En attente',
                                'accepte' => 'Accepté',
                                'refuse' => 'Refusé',
                            ])
                            ->default('en_attente')
                            ->required()
                            ->native(false),

                        Textarea::make('commentaire_admin')
                            ->label('Note administrative')
                            ->placeholder('Raison du refus ou détails...'),
                    ])->columns(2)
                    ->visible(function () {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user && $user->hasRole('admin');
                    }),
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
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paye' => 'success',
                        'maladie' => 'danger',
                        'sans_solde' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_debut')->date('d/m/Y')->label('Début')->sortable(),
                TextColumn::make('date_fin')->date('d/m/Y')->label('Fin')->sortable(),
                TextColumn::make('jours')->suffix(' Jours')->label('Durée')->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepte' => 'success',
                        'refuse' => 'danger',
                        'en_attente' => 'warning',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'accepte' => 'heroicon-o-check-circle',
                        'refuse' => 'heroicon-o-x-circle',
                        'en_attente' => 'heroicon-o-clock',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->options([
                        'en_attente' => 'En attente',
                        'accepte' => 'Validés',
                        'refuse' => 'Refusés',
                    ])
                    ->label('Filtrer par statut'),
                    
                SelectFilter::make('type')
                    ->options([
                        'paye' => 'Congé Payé',
                        'maladie' => 'Maladie',
                        'sans_solde' => 'Sans Solde',
                        'maternite' => 'Maternité',
                        'recuperation' => 'Récupération',
                    ])
                    ->label('Filtrer par type'),
            ])
            ->actions([
                // 👇 LA NOUVELLE LOGIQUE EST ICI 👇
                Tables\Actions\Action::make('valider')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider le congé')
                    ->modalDescription('Êtes-vous sûr de vouloir valider cette demande de congé ?')
                    ->modalSubmitActionLabel('Oui, valider')
                    ->modalCancelActionLabel('Annuler')
                    ->action(function (Conge $record) {
                        // 1. On valide la demande de congé dans tous les cas
                        $record->update(['statut' => 'accepte']);

                        // 2. On utilise Carbon pour comparer les dates
                        $aujourdHui = Carbon::today();
                        $dateDebut = Carbon::parse($record->date_debut);

                        // 3. Si le congé commence aujourd'hui ou dans le passé
                        if ($dateDebut->lessThanOrEqualTo($aujourdHui)) {
                            $dossier = DossierEmploye::where('employe_id', $record->employe_id)->first();
                            if ($dossier) {
                                $dossier->update(['statut' => 'en_conge']);
                            }
                            $titreNotification = 'Congé validé et statut mis à jour !';
                            $bodyNotification = "L'employé est actuellement marqué en congé.";
                        } 
                        // 4. Si le congé est prévu pour plus tard (ex: le 15)
                        else {
                            $titreNotification = 'Congé validé pour le futur !';
                            $bodyNotification = "Le statut passera automatiquement en congé le " . $dateDebut->format('d/m/Y') . ".";
                        }

                        // 5. On affiche la notification adéquate
                        Notification::make()
                            ->title($titreNotification)
                            ->body($bodyNotification)
                            ->success()
                            ->send();
                    })
                    ->visible(function (Conge $record) {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $record->statut === 'en_attente' && $user && $user->hasRole('admin');
                    }),

                Tables\Actions\Action::make('refuser')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refuser le congé')
                    ->modalDescription('Êtes-vous sûr de vouloir refuser cette demande ?')
                    ->modalSubmitActionLabel('Oui, refuser')
                    ->modalCancelActionLabel('Annuler')
                    ->action(function (Conge $record) {
                        $record->update(['statut' => 'refuse']);
                        
                        Notification::make()
                            ->title('Congé refusé')
                            ->danger()
                            ->send();
                    })
                    ->visible(function (Conge $record) {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $record->statut === 'en_attente' && $user && $user->hasRole('admin');
                    }),
                    
                Tables\Actions\Action::make('terminer')
                    ->label('Marquer Terminé')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->action(function (Conge $record) {
                        $dossier = DossierEmploye::where('employe_id', $record->employe_id)->first();
                        if ($dossier) {
                            $dossier->update(['statut' => 'actif']);
                        }
                        Notification::make()
                            ->title('Statut réinitialisé')
                            ->body('L\'employé est de nouveau actif.')
                            ->success()
                            ->send();
                    })
                    ->visible(function (Conge $record) {
                        /** @var \App\Models\User|null $user */
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $record->statut === 'accepte' && $user && $user->hasRole('admin');
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConges::route('/'),
            'create' => Pages\CreateConge::route('/create'),
            'edit' => Pages\EditConge::route('/{record}/edit'),
        ];
    }
}