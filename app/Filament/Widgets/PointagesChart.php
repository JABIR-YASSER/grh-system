<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pointage;
use Carbon\Carbon;

class PointagesChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution des Présences (7 derniers jours)';
    protected static ?int $sort = 2; 
    protected int | string | array $columnSpan = '1';
    protected static ?string $maxHeight = '250px';

    // 👇 LA SÉCURITÉ : Restreindre l'affichage du graphique 👇
    public static function canView(): bool
    {
        // Si vous utilisez Spatie Permission (recommandé) :
        // return auth()->user()->hasRole('Administrateur'); // ou 'admin' selon votre base
        
        // Méthode simple et infaillible pour votre compte de test actuel :
        return auth('web')->user()?->email === 'admin@grh.com'
            && ! request()->routeIs('filament.app.pages.dashboard');
    }

    protected function getData(): array
    {
        $donnees = [];
        $etiquettes = [];

        for ($i = 6; $i >= 0; $i--) {
            $jour = Carbon::now()->subDays($i);
            $etiquettes[] = $jour->format('d/m'); 
            $compteur = Pointage::whereDate('created_at', $jour->toDateString())->count();
            $donnees[] = $compteur;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Employés présents',
                    'data' => $donnees,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $etiquettes,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}