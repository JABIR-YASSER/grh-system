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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DossierEmployeResource extends Resource
{
    protected static ?string $model = DossierEmploye::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $navigationLabel = 'Dossiers Employés';

    // --- SÉCURITÉ ET ACCÈS ---

    public static function canViewAny(): bool
    {
        // Tout le monde peut voir la ressource (pour que l'employé voie son dossier)
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

        // 🛡️ FILTRE : L'employé ne voit QUE son dossier, l'admin voit TOUT
        if ($user && !$user->hasRole('admin')) {
            $query->whereHas('employe', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query;
    }

    // --- FORMULAIRE ---

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
                                    ->disabled(!$isAdmin) // Seul l'admin peut changer le lien
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

                                Select::make('statut')
                                    ->options([
                                        'actif' => 'Actif',
                                        'archive' => 'Archivé',
                                        'suspendu' => 'Suspendu',
                                    ])
                                    ->default('actif')
                                    ->required()
                                    ->native(false)
                                    ->disabled(!$isAdmin),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    // --- TABLEAU ---

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employe.user.nom')
                    ->label('Nom Employé')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('numero')
                    ->label('N° Dossier')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('cin')
                    ->label('CIN')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable(),

                TextColumn::make('sexe')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'H' ? 'info' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'H' ? 'Homme' : 'Femme')
                    ->sortable(),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'actif' => 'success',
                        'archive' => 'gray',
                        'suspendu' => 'danger',
                        default => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->options(['actif' => 'Actif', 'archive' => 'Archivé', 'suspendu' => 'Suspendu']),
                SelectFilter::make('sexe')
                    ->options(['H' => 'Homme', 'F' => 'Femme']),
            ])
            ->actions([
                // L'employé pourra "Voir" son dossier même s'il ne peut pas le modifier
                Tables\Actions\ViewAction::make(), 
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDossierEmployes::route('/'),
            'create' => Pages\CreateDossierEmploye::route('/create'),
            'view' => Pages\ViewDossierEmploye::route('/{record}'), // Page de vue pour l'employé
            'edit' => Pages\EditDossierEmploye::route('/{record}/edit'),
        ];
    }
}