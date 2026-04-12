<?php

namespace App\Filament\Widgets;

use App\Models\DossierEmploye;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsConge extends BaseWidget
{
    protected function getStats(): array
    {
        // On compte uniquement ceux qui ont le statut automatisé par notre commande
        $nbEnConge = DossierEmploye::where('statut', 'en_conge')->count();
        $totalEffectif = DossierEmploye::where('statut', 'actif')->count() + $nbEnConge;

        return [
            Stat::make('Employés en congé', $nbEnConge)
                ->description('Absents aujourd\'hui')
                ->descriptionIcon('heroicon-m-clock')
                ->color($nbEnConge > 0 ? 'info' : 'gray')
                ->chart([
                    // Tu peux simuler une courbe de tendance ici
                    $nbEnConge, $nbEnConge + 1, $nbEnConge - 1, $nbEnConge
                ]),

            Stat::make('Taux de présence', $totalEffectif > 0 ? round((($totalEffectif - $nbEnConge) / $totalEffectif) * 100) . '%' : '100%')
                ->description('Disponibilité de l\'effectif')
                ->color('success'),
        ];
    }
}