<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Pointage;
use App\Models\Employe;
use Carbon\Carbon;

class PointagesStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Répartition des Présences (Aujourd\'hui)';
    protected static ?string $maxHeight = '250px';
    // Le graphique prend 1 seule colonne (pour être plus petit)
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $totalEmployes = Employe::count();
        $presentsAujourdhui = Pointage::whereDate('date', Carbon::today())->count();
        $absents = $totalEmployes - $presentsAujourdhui;

        // Sécurité pour éviter les nombres négatifs si erreur de base de données
        $absents = $absents < 0 ? 0 : $absents;

        return [
            'datasets' => [
                [
                    'label' => 'Employés',
                    'data' => [$presentsAujourdhui, $absents],
                    'backgroundColor' => [
                        '#10b981', // Vert pour les présents
                        '#ef4444', // Rouge pour les absents
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => ['Présents', 'Absents / Non pointés'],
        ];
    }

    public static function canView(): bool
    {
        // On masque le widget si on est sur la page d'accueil (Dashboard)
        // Mais il reste actif pour les autres pages !
        return ! request()->routeIs('filament.app.pages.dashboard');
    }
    protected function getType(): string
    {
        return 'doughnut'; // Graphique en forme de Donut !
    }
}