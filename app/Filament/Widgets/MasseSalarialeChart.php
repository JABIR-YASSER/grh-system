<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MasseSalarialeChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution de la Masse Salariale (Net à Payer)';
    
    // Le graphique financier prend toute la largeur pour être bien lisible
    protected int | string | array $columnSpan = '1';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $donnees = [];
        $labels = [];

        // On boucle sur les 6 derniers mois
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            
            // Label pour le graphique (ex: "Avril 2026")
            $labels[] = $date->translatedFormat('F Y');

            // 1. On récupère le numéro du mois (format "01", "02"...)
            $moisNumerique = $date->format('m'); 
            
            // 2. On calcule la somme dans ta table 'paies'
            $total = DB::table('paies')
                ->where('mois', $moisNumerique) // Cherche dans ta colonne 'mois'
                ->where('annee', $date->year)   // Cherche dans ta colonne 'annee'
                ->sum('net_a_payer');

            $donnees[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total des salaires versés (DH)',
                    'data' => $donnees,
                    'fill' => 'start',
                    'borderColor' => '#10b981', // Vert émeraude
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
    public static function canView(): bool
    {
        // On masque le widget si on est sur la page d'accueil (Dashboard)
        // Mais il reste actif pour les autres pages !
        return ! request()->routeIs('filament.app.pages.dashboard');
    }
}