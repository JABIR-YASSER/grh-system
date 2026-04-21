<?php

namespace App\Filament\Widgets;

use App\Models\Paie;
use App\Models\Conge;
use App\Models\Employe;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EmployeOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user && !$user->hasRole('admin');
    }

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employe = $user->employe;

        if (!$employe) return [];

        // 1. Solde de congés (sur une base de 18 jours)
        $joursUtilises = Conge::where('employe_id', $employe->id)
            ->where('statut', 'accepte')
            ->sum('jours');
        $soldeConges = max(0, 18 - $joursUtilises);

        // 2. Suivi de la dernière demande de congé 
        $derniereDemande = Conge::where('employe_id', $employe->id)
            ->latest()
            ->first();

        // Logique pour la carte de statut
        $statutLabel = "Aucune demande";
        $statutDesc = "Vous n'avez pas de demande active";
        $statutColor = "gray";
        $statutIcon = "heroicon-m-clipboard";

        if ($derniereDemande) {
            match ($derniereDemande->statut) {
                'en_attente' => [
                    $statutLabel = "Demande en cours...",
                    $statutDesc = "Votre demande est en cours de traitement ⏳", // 👈 Ton message ici
                    $statutColor = "warning",
                    $statutIcon = "heroicon-m-clock",
                ],
                'accepte' => [
                    $statutLabel = "Demande Acceptée",
                    $statutDesc = "Profitez bien de vos vacances ! ✈️",
                    $statutColor = "success",
                    $statutIcon = "heroicon-m-check-circle",
                ],
                'refuse' => [
                    $statutLabel = "Demande Refusée",
                    $statutDesc = "Consultez le motif dans vos dossiers ❌",
                    $statutColor = "danger",
                    $statutIcon = "heroicon-m-x-circle",
                ],
            };
        }

        // 3. Dernière paie
        $dernierePaie = Paie::where('employe_id', $employe->id)
            ->where('statut', 'paye')
            ->latest()
            ->first();

        return [
            Stat::make('Mon Solde de Congés', $soldeConges . ' Jours')
                ->description('Jours restants disponibles')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Statut de ma Demande', $statutLabel)
                ->description($statutDesc)
                ->descriptionIcon($statutIcon)
                ->color($statutColor),

            Stat::make('Dernier Salaire', $dernierePaie ? number_format($dernierePaie->net_a_payer, 2, ',', ' ') . ' DH' : '---')
                ->description($dernierePaie ? 'Mois de ' . $dernierePaie->mois : 'En attente de virement')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }
}