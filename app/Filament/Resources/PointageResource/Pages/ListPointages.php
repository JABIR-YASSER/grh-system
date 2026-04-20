<?php

namespace App\Filament\Resources\PointageResource\Pages;

use App\Filament\Resources\PointageResource;
use Filament\Actions;
use Filament\Resources\Pages\Page; // 👈 1. ON IMPORTE 'Page' AU LIEU DE 'ListRecords'
use Illuminate\Support\Facades\Auth;

// Logic & Models
use App\Models\Pointage;
use App\Imports\PointagesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

// UI
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

// Widgets
use App\Filament\Widgets\PointagesChart;
use App\Filament\Widgets\PointagesStatusChart;
use App\Filament\Widgets\EffectifsDepartementChart;
use App\Filament\Widgets\MasseSalarialeChart;
use App\Filament\Widgets\PonctualiteChart;
use App\Filament\Widgets\RetardsChart;
    
class ListPointages extends Page // 👈 2. ON ÉTEND 'Page' ICI
{
    protected static string $resource = PointageResource::class;

    // Le lien vers ton fichier Blade
    protected static string $view = 'filament.pages.pointages-stats';

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PointagesStatusChart::class, // 1. Présences (Donut)
            PonctualiteChart::class,     // 2. Ponctualité (Donut)
            PointagesChart::class,       // 3. Évolution Présences (Ligne)
            RetardsChart::class,         // 4. Évolution Retards (Barres)
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Bouton Importer
            Actions\Action::make('import')
                ->label('Importer Badgeuse')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->visible(fn () => Auth::user()?->hasRole('admin'))
                ->form([
                    FileUpload::make('fichier_excel')
                        ->label('Fichier Excel ou CSV')
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $path = Storage::disk('public')->path($data['fichier_excel']);
                        Excel::import(new PointagesImport, $path);
                        Notification::make()->title('Importation réussie')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('Erreur d\'importation')->danger()->send();
                    }
                }),

            // Bouton Vider
            Actions\Action::make('clear')
                ->label('Vider la liste')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => Auth::user()?->hasRole('admin'))
                ->requiresConfirmation()
                ->action(function () {
                    Pointage::query()->delete();
                    Notification::make()->title('Données effacées')->warning()->send();
                }),
        ];
    }
}