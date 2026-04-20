<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pointage;
use Carbon\Carbon;

class RetardsChart extends ChartWidget
{
    protected static ?string $heading = 'Nombre de Retards (7 derniers jours)';
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $donnees = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $jour = Carbon::now()->subDays($i);
            $labels[] = $jour->translatedFormat('d/m');

            // On compte le nombre de retards pour ce jour-là
            $retards = Pointage::whereDate('date', $jour->toDateString())
                ->whereTime('heure_arrivee', '>', '09:15:00')
                ->count();

            $donnees[] = $retards;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Employés en retard',
                    'data' => $donnees,
                    'backgroundColor' => '#ef4444', // Rouge pour bien signaler les retards
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
    public static function canView(): bool
    {
        // On masque le widget si on est sur la page d'accueil (Dashboard)
        // Mais il reste actif pour les autres pages !
        return ! request()->routeIs('filament.app.pages.dashboard');
    }
}