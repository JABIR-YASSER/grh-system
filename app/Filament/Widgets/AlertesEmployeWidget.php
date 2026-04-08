<?php

namespace App\Filament\Widgets;

use App\Models\Conge;
use App\Models\Employe;
use App\Models\Pointage;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlertesEmployeWidget extends BaseWidget
{
    // S'affiche tout en haut
    protected static ?int $sort = -1;

    // Seulement visible pour les employés
    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->hasRole('employe');
    }

    protected function getStats(): array
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        $employe = Employe::where('user_id', $user->id)->first();

        // Si l'utilisateur n'a pas de fiche employé associée, on n'affiche rien
        if (!$employe) {
            return [];
        }

        $stats = [];

        // 1. Alerte Pointage : S'il n'a pas pointé aujourd'hui
        $aPointeAujourdhui = Pointage::where('employe_id', $employe->id)
            ->where('date', Carbon::today()->toDateString())
            ->exists();

        // On affiche l'alerte s'il n'a pas pointé (et on ignore le Dimanche)
        if (!$aPointeAujourdhui && !Carbon::today()->isSunday()) { 
            $stats[] = Stat::make('⚠️ Pointage Manquant', 'Vous n\'avez pas encore pointé aujourd\'hui !')
                ->description('N\'oubliez pas d\'enregistrer votre présence.')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'ring-2 ring-warning-500 shadow-lg shadow-warning-500/30',
                ]);
        }

        // 2. Alerte Congés : S'il a des congés en attente
        $congesEnAttente = Conge::where('employe_id', $employe->id)
            ->where('statut', 'en_attente')
            ->count();

        if ($congesEnAttente > 0) {
            $stats[] = Stat::make('⏳ Congés en cours', $congesEnAttente . ' demande(s) en attente')
                ->description('Votre demande est en cours de validation par l\'administration.')
                // 👇 L'ERREUR ÉTAIT ICI : L'icône a été remplacée par une valide 👇
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info');
        }

        return $stats;
    }
}