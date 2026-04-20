<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Employe;
use Illuminate\Support\Facades\DB;

class EffectifsDepartementChart extends ChartWidget
{
    protected static ?string $heading = 'Effectifs par Département';
    protected int | string | array $columnSpan = 1;
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        // On fait une jointure pour récupérer le 'libelle' du département au lieu de l'ID
        $donnees = DB::table('employes')
            ->join('departements', 'employes.departement_id', '=', 'departements.id')
            ->select('departements.libelle', DB::raw('count(*) as total'))
            ->groupBy('departements.libelle')
            ->pluck('total', 'libelle')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Nombre d\'employés',
                    'data' => array_values($donnees),
                    'backgroundColor' => [
                        '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899' // Couleurs variées pour chaque barre
                    ],
                    'borderRadius' => 6,
                ],
            ],
            'labels' => array_keys($donnees),
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