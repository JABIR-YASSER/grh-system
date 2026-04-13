<?php

namespace App\Filament\Widgets;

use App\Models\Employe;
use App\Models\Paie;
use App\Models\Departement;
use App\Models\DossierEmploye;
use App\Models\Pointage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    // 👇 Permet d'afficher ce widget tout en haut du Dashboard
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        
        // Seul l'admin a le droit de voir ces statistiques globales
        return $user && $user->hasRole('admin');
    }

    protected function getStats(): array
    {
        // --- 1. Calcul de la Masse Salariale du mois précédent ---
        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        // On cible le mois dernier
        $moisCible = $nomsMois[now()->subMonth()->month];
        $anneeCible = now()->subMonth()->year;

        $masseSalariale = Paie::where('mois', $moisCible)
                              ->where('annee', $anneeCible)
                              ->sum('net_a_payer');

        // --- 2. Calcul des Métriques Avancées (Taux de Présence) ---
        $effectifTotal = Employe::count();
        
        // Employés officiellement en congé aujourd'hui
        $employesEnConge = DossierEmploye::where('statut', 'en_conge')->count();
        
        // Pointages enregistrés aujourd'hui
        $pointagesAujourdhui = Pointage::whereDate('date', Carbon::today())->count();
        
        // Calcul du taux : (Présents / Employés Attendus) * 100
        // (On soustrait les employés en congé pour ne pas fausser la statistique)
        $employesAttendus = max(1, $effectifTotal - $employesEnConge); // max(1) évite la division par zéro (erreur fatale)
        $tauxPresence = round(($pointagesAujourdhui / $employesAttendus) * 100, 1);

        return [
            // Statistique 1 : Le taux de présence réactif
            Stat::make('Taux de Présence (Aujourd\'hui)', $tauxPresence . ' %')
                ->description($pointagesAujourdhui . ' employé(s) présent(s)')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($tauxPresence >= 90 ? 'success' : 'warning')
                ->chart([30, 50, 70, 85, $tauxPresence]), // Graphique dynamique qui monte vers le taux actuel

            // Statistique 2 : Le suivi des congés
            Stat::make('Employés en Congé', $employesEnConge)
                ->description('Actuellement absents (justifiés)')
                ->descriptionIcon('heroicon-m-sun')
                ->color('info'),

            // Statistique 3 : La finance
            Stat::make('Masse Salariale (' . $moisCible . ')', number_format($masseSalariale, 2, ',', ' ') . ' DH')
                ->description('Net versé le mois précédent')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),
        ];
    }
}