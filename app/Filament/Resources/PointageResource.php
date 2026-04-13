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

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $navigationLabel = 'Pointages';

    public static function canViewAny(): bool
    {
        // 🔒 Sécurité : Seuls les administrateurs ont accès à cette interface
        return Auth::user()?->hasRole('admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('employe_id')
                    ->relationship('employe', 'matricule')
                    ->label('Employé (Matricule)')
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('Date du pointage')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                    
                Forms\Components\TimePicker::make('heure_arrivee')
                    ->label('Heure d\'arrivée')
                    ->required(),
                    
                Forms\Components\TimePicker::make('heure_depart')
                    ->label('Heure de départ'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employe.user.nom')
                    ->label('Employé')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employe.matricule')
                    ->label('Matricule')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d/m/Y')
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
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_debut')
                            ->label('Du')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_fin')
                            ->label('Au')
                            ->native(false),
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
                // ✏️ L'administrateur peut modifier pour corriger une erreur
                Tables\Actions\EditAction::make(),
                
                // ❌ La suppression (DeleteAction) est absente pour des raisons légales et d'audit
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // 👇 Ajout du bouton d'export Excel demandé dans le CDC 👇
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()
                        ->label('Exporter vers Excel')
                        ->color('success')
                        ->icon('heroicon-o-document-arrow-down'),
                ]),
            ])
            ->defaultSort('date', 'desc');
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