<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pointage;
use Carbon\Carbon;

class PonctualiteChart extends ChartWidget
{
    protected static ?string $heading = 'Ponctualité (Aujourd\'hui)';
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        // On compte ceux arrivés avant ou à 09:15 (tolérance)
        $aLHeure = Pointage::whereDate('date', today())
            ->whereTime('heure_arrivee', '<=', '09:15:00')
            ->count();

        // On compte ceux arrivés après 09:15
        $enRetard = Pointage::whereDate('date', today())
            ->whereTime('heure_arrivee', '>', '09:15:00')
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'Employés',
                    'data' => [$aLHeure, $enRetard],
                    'backgroundColor' => ['#10b981', '#f59e0b'], // Vert (À l'heure), Orange (Retard)
                ],
            ],
            'labels' => ['À l\'heure', 'En retard'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
    public static function canView(): bool
    {
        // On masque le widget si on est sur la page d'accueil (Dashboard)
        // Mais il reste actif pour les autres pages !
        return ! request()->routeIs('filament.app.pages.dashboard');
    }
}