<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DossierEmployeResource\Pages;
use App\Models\DossierEmploye;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons; // 👈 Import ajouté ici
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class DossierEmployeResource extends Resource
{
    protected static ?string $model = DossierEmploye::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $navigationLabel = 'Dossiers Employés';

    public static function canViewAny(): bool
    {
        return Auth::check();
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user && !$user->hasRole('admin')) {
            $query->whereHas('employe', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $isAdmin = Auth::user()?->hasRole('admin') ?? false;

        return $form
            ->schema([
                Tabs::make('Dossier Complet')
                    ->tabs([
                        Tabs\Tab::make('Identité & État Civil')
                            ->icon('heroicon-m-user')
                            ->schema([
                                Select::make('employe_id')
                                    ->relationship('employe', 'matricule')
                                    ->label('Lier à l\'Employé (Matricule)')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(!$isAdmin)
                                    ->columnSpanFull(),

                                TextInput::make('numero')
                                    ->label('N° Dossier')
                                    ->default('DOS-' . date('Y') . '-' . rand(1000, 9999))
                                    ->required()
                                    ->readOnly(),

                                TextInput::make('cin')
                                    ->label('N° CIN')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->disabled(!$isAdmin),

                                Select::make('sexe')
                                    ->options(['H' => 'Homme', 'F' => 'Femme'])
                                    ->required()
                                    ->native(false)
                                    ->disabled(!$isAdmin),

                                DatePicker::make('date_naissance')
                                    ->label('Date de naissance')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->disabled(!$isAdmin),

                                Select::make('situation_familiale')
                                    ->label('Situation Familiale')
                                    ->options([
                                        'celibataire' => 'Célibataire',
                                        'marie' => 'Marié(e)',
                                        'divorce' => 'Divorcé(e)',
                                        'veuf' => 'Veuf(ve)',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->disabled(!$isAdmin),

                                TextInput::make('nombre_enfants')
                                    ->label('Nombre d\'enfants')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled(!$isAdmin),
                            ])->columns(2),

                        Tabs\Tab::make('Contact & Urgence')
                            ->icon('heroicon-m-phone')
                            ->schema([
                                TextInput::make('telephone')
                                    ->label('Téléphone Personnel')
                                    ->tel()
                                    ->required()
                                    ->disabled(!$isAdmin),

                                TextInput::make('email_personnel')
                                    ->label('Email Personnel')
                                    ->email()
                                    ->disabled(!$isAdmin),

                                Textarea::make('adresse')
                                    ->label('Adresse Résidentielle')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->disabled(!$isAdmin),

                                TextInput::make('contact_urgence')
                                    ->label('Contact d\'urgence (Nom & Tél)')
                                    ->columnSpanFull()
                                    ->disabled(!$isAdmin),
                            ])->columns(2),

                        Tabs\Tab::make('Administratif & Paie')
                            ->icon('heroicon-m-banknotes')
                            ->schema([
                                TextInput::make('rib')
                                    ->label('RIB (24 chiffres)')
                                    ->length(24)
                                    ->disabled(!$isAdmin),

                                TextInput::make('cnss')
                                    ->label('N° de CNSS')
                                    ->disabled(!$isAdmin),

                                // 👇 OPTIMISATION : Les ToggleButtons pour le Statut !
                                ToggleButtons::make('statut')
                                    ->label('Statut du dossier')
                                    ->options([
                                        'actif' => 'Actif',
                                        'en_conge' => 'En congé',
                                        'archive' => 'Archivé', 
                                    ])
                                    ->colors([
                                        'actif' => 'success',
                                        'en_conge' => 'info',
                                        'archive' => 'gray',
                                    ])
                                    ->icons([
                                        'actif' => 'heroicon-m-check-badge',
                                        'en_conge' => 'heroicon-m-sun',
                                        'archive' => 'heroicon-m-archive-box',
                                    ])
                                    ->inline()
                                    ->default('actif')
                                    ->required()
                                    ->disabled(!$isAdmin)
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.user.nom')
                    ->label('Nom Employé')
                    ->formatStateUsing(fn ($record) => $record->employe->user->nom . ' ' . $record->employe->user->prenom)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employe.user', function (Builder $q) use ($search) {
                            $q->where('nom', 'like', "%{$search}%")
                              ->orWhere('prenom', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                TextColumn::make('numero')
                    ->label('N° Dossier')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('cin')
                    ->label('CIN')
                    ->icon('heroicon-m-identification')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->icon('heroicon-m-phone')
                    ->searchable(),

                TextColumn::make('sexe')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'H' ? 'info' : 'danger')
                    ->icon(fn (string $state): string => $state === 'H' ? 'heroicon-m-user' : 'heroicon-m-user-minus')
                    ->formatStateUsing(fn (string $state): string => $state === 'H' ? 'Homme' : 'Femme')
                    ->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'en_conge' => 'info',
                        'archive' => 'gray',
                        'suspendu' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('sexe')
                    ->options(['H' => 'Homme', 'F' => 'Femme']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), 
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('archiver')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => 
                        $record->statut === 'archive' || 
                        ($record->employe && $record->employe->user_id === Auth::id())
                    )
                    ->action(function ($record) {
                        $record->update(['statut' => 'archive']);
                        Notification::make()
                            ->title('Dossier archivé avec succès')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->statut !== 'archive')
                    ->tooltip('Seuls les dossiers archivés peuvent être supprimés.'),
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
            'index' => Pages\ListDossierEmployes::route('/'),
            'create' => Pages\CreateDossierEmploye::route('/create'),
            'view' => Pages\ViewDossierEmploye::route('/{record}'), 
            'edit' => Pages\EditDossierEmploye::route('/{record}/edit'),
        ];
    }
}