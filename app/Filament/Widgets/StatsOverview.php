<?php

namespace App\Filament\Widgets;

use App\Models\Employe;
use App\Models\Paie;
use App\Models\Departement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    // 👇 Permet d'afficher ce widget tout en haut du Dashboard
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Seul l'admin a le droit de voir ces statistiques globales
        return $user && $user->hasRole('admin');
    }

    protected function getStats(): array
    {
        // 1. Définir la période cible (le mois précédent, comme dans PaieResource)
        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        $moisCible = $nomsMois[now()->subMonth()->month];
        $anneeCible = now()->subMonth()->year;

        // 2. Calcul correct de la masse salariale avec le nom du mois en texte
        $masseSalariale = Paie::where('mois', $moisCible)
                              ->where('annee', $anneeCible)
                              ->sum('net_a_payer');

        return [
            Stat::make('Total Employés', Employe::count())
                ->description('Effectif global de l\'entreprise')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]), // Crée un petit graphique décoratif en fond

            // Le titre affiche dynamiquement de quel mois on parle
            Stat::make('Masse Salariale (' . $moisCible . ')', number_format($masseSalariale, 2, ',', ' ') . ' DH')
                ->description('Net versé le mois précédent')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Départements', Departement::count())
                ->description('Services opérationnels actifs')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }
}