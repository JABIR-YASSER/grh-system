<?php

namespace App\Filament\Widgets;

use App\Models\Paie;
use App\Models\DossierEmploye;
use App\Models\Pointage;
use App\Models\Conge; // 👈 Import ajouté pour l'alerte
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && $user->hasRole('admin');
    }

    protected function getStats(): array
    {
        // --- 1. Finance ---
        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        $moisCible = $nomsMois[now()->subMonth()->month];
        $anneeCible = now()->subMonth()->year;

        $masseSalariale = Paie::where('mois', $moisCible)
                              ->where('annee', $anneeCible)
                              ->sum('net_a_payer');

        // --- 2. Présence et Congés (CORRIGÉ) ---
        // Le vrai dénominateur : Uniquement les employés avec un dossier "actif"
        $effectifActif = DossierEmploye::where('statut', 'actif')->count();
        
        $employesEnConge = DossierEmploye::where('statut', 'en_conge')->count();
        $pointagesAujourdhui = Pointage::whereDate('date', Carbon::today())->count();
        
        // Sécurité division par zéro
        // Attention : Si $effectifActif est 0 (système vide), on met 1 pour ne pas planter.
        $totalAttendu = max(1, $effectifActif); 
        
        // Le taux ne peut plus dépasser 100% grâce à min()
        $tauxPresence = round(min(100, ($pointagesAujourdhui / $totalAttendu) * 100), 1);

        // --- 3. Alertes ---
        // On compte les congés qui attendent une validation
        $congesEnAttente = Conge::where('statut', 'en_attente')->count();


        // --- CREATION DES CARTES ---
        $stats = [];

        // On affiche l'alerte SEULEMENT s'il y a des congés à valider
        

        $stats[] = Stat::make('Taux de Présence (Aujourd\'hui)', $tauxPresence . ' %')
            ->description($pointagesAujourdhui . ' employé(s) présent(s)')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color($tauxPresence >= 90 ? 'success' : 'warning')
            ->chart([0, 50, $tauxPresence]); // Petite animation de courbe

        $stats[] = Stat::make('Employés en Congé', $employesEnConge)
            ->description('Actuellement absents (justifiés)')
            ->descriptionIcon('heroicon-m-sun')
            ->color('info');

        $stats[] = Stat::make('Masse Salariale (' . $moisCible . ')', number_format($masseSalariale, 2, ',', ' ') . ' DH')
            ->description('Net versé le mois précédent')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('gray');

        return $stats;
    }
}