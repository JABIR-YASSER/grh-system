<?php

namespace App\Filament\Widgets;

use App\Models\DossierEmploye;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsConge extends BaseWidget
{
    // Fixe l'ordre d'affichage (en haut)
    protected static ?int $sort = 1;

    /**
     * 🔒 SÉCURITÉ : Définit qui peut voir ce widget
     */
    public static function canView(): bool
    {
        // Le widget ne s'affiche que si l'utilisateur a le rôle 'admin'
        return Auth::user()?->hasRole('admin') ?? false;
    }

    protected function getStats(): array
    {
        // 1. Compter les employés actuellement marqués "En congé"
        $nbEnConge = DossierEmploye::where('statut', 'en_conge')->count();

        // 2. Compter les employés "Actifs"
        $nbActifs = DossierEmploye::where('statut', 'actif')->count();

        // 3. Calculer le total (Actifs + En congé) pour le taux
        $totalEffectif = $nbActifs + $nbEnConge;

        // 4. Calcul du taux de présence
        $tauxPresence = $totalEffectif > 0 
            ? round(($nbActifs / $totalEffectif) * 100) 
            : 100;

        return [
            // Carte : Nombre d'employés en congé
            Stat::make('Employés en congé', $nbEnConge)
                ->description('Absences recensées aujourd\'hui')
                ->descriptionIcon('heroicon-m-clock')
                ->color($nbEnConge > 0 ? 'info' : 'gray'),

            // Carte : Taux de présence
            Stat::make('Taux de présence', $tauxPresence . '%')
                ->description('Disponibilité de l\'effectif total')
                ->descriptionIcon('heroicon-m-users')
                ->color($tauxPresence > 80 ? 'success' : 'warning')
                ->chart([7, 10, 5, 2, 10, 3, 15]), // Petite décoration graphique
        ];
    }
}