<?php

namespace App\Filament\Widgets;

use App\Models\Conge;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AlertesAdminWidget extends BaseWidget
{
    // On force ce widget à s'afficher tout en haut (avant les statistiques)
    protected static ?int $sort = -1;

    // Seulement visible pour l'administrateur
    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->hasRole('admin');
    }

    protected function getStats(): array
    {
        $congesEnAttente = Conge::where('statut', 'en_attente')->count();

        // Si tout est traité (0 attente), on n'affiche aucune carte (tableau vide)
        if ($congesEnAttente === 0) {
            return [];
        }

        // Sinon, on affiche une grosse alerte rouge
        return [
            Stat::make('⚠️ Action Requise', $congesEnAttente . ' Demande(s) de congé en attente')
                ->description('Cliquez sur Gestion RH > Congés & Absences pour les traiter.')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'ring-2 ring-danger-500 shadow-lg shadow-danger-500/30',
                ]),
        ];
    }
}