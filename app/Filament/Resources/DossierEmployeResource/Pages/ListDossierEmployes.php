<?php

namespace App\Filament\Resources\DossierEmployeResource\Pages;

use App\Filament\Resources\DossierEmployeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\DossierEmploye;

class ListDossierEmployes extends ListRecords
{
    protected static string $resource = DossierEmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = Auth::user();

        // 🛡️ Si l'utilisateur n'est PAS admin, on ne retourne AUCUN onglet
        if ($user && !$user->hasRole('admin')) {
            return [];
        }

        // 👑 Si c'est l'admin, on affiche la barre de filtrage complète
        return [
            'actifs' => Tab::make('Dossiers Actifs')
                ->icon('heroicon-m-check-badge')
                ->badge(DossierEmploye::where('statut', 'actif')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'actif')),

            'en_conge' => Tab::make('En Congé / Absents')
                ->icon('heroicon-m-sun')
                ->badge(DossierEmploye::where('statut', 'en_conge')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'en_conge')),

            'archives' => Tab::make('Archives')
                ->icon('heroicon-m-archive-box')
                ->badge(DossierEmploye::where('statut', 'archive')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'archive')),

            'tous' => Tab::make('Toute la base')
                ->icon('heroicon-m-list-bullet'),
        ];
    }
}