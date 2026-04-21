<?php

namespace App\Filament\Resources\CongeResource\Pages;

use App\Filament\Resources\CongeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab; // 👈 N'oublie pas cet import !
use Illuminate\Database\Eloquent\Builder;
use App\Models\Conge; // 👈 N'oublie pas d'importer ton modèle !

class ListConges extends ListRecords
{
    protected static string $resource = CongeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // 👇 AJOUTE CETTE FONCTION POUR CRÉER LES ONGLETS 👇
    public function getTabs(): array
    {
        return [
            // 1. Onglet par défaut (Ce qu'il faut traiter)
            'a_traiter' => Tab::make('À traiter')
                ->icon('heroicon-m-inbox')
                ->badge(Conge::where('statut', 'en_attente')->count()) // Affiche le nombre de demandes en attente !
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'en_attente')),

            // 2. Onglet des archives (Acceptés ou Refusés)
            'archives' => Tab::make('Archives')
                ->icon('heroicon-m-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('statut', ['accepte', 'refuse'])),

            // 3. Onglet pour tout voir (Optionnel mais pratique)
            'tous' => Tab::make('Toutes les demandes')
                ->icon('heroicon-m-list-bullet'),
        ];
    }
}