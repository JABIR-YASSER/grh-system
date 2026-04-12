<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CongeResource\Pages;
use App\Models\Conge;
use App\Models\DossierEmploye; // Make sure to import the DossierEmploye model
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
use Filament\Notifications\Notification; // Import for notifications
use Illuminate\Database\Eloquent\Builder;

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
                Tables\Actions\Action::make('valider')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Valider le congé')
                    ->modalDescription('Êtes-vous sûr de vouloir valider cette demande de congé ? Le statut de l\'employé sera mis à jour.')
                    ->modalSubmitActionLabel('Oui, valider')
                    ->modalCancelActionLabel('Annuler')
                    ->action(function (Conge $record) {
                        // 1. Update the leave status
                        $record->update(['statut' => 'accepte']);

                        // 2. Find and update the employee's dossier status
                        $dossier = DossierEmploye::where('employe_id', $record->employe_id)->first();
                        if ($dossier) {
                            $dossier->update(['statut' => 'en_conge']);
                        }

                        // 3. Notify the admin
                        Notification::make()
                            ->title('Congé validé')
                            ->body('Le statut de l\'employé a été mis à jour avec succès.')
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
                    
                // Optional: Action to mark leave as finished and set employee back to active
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
                        // Only show if it's accepted and the user is an admin
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