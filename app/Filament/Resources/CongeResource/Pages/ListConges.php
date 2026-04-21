<?php

namespace App\Filament\Resources\CongeResource\Pages;

use App\Filament\Resources\CongeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Conge;
use Illuminate\Support\Facades\Auth; // 👈 Import important pour l'utilisateur actuel

class ListConges extends ListRecords
{
    protected static string $resource = CongeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $employeId = $user->employe?->id;

        return [
            'a_traiter' => Tab::make('À traiter')
                ->icon('heroicon-m-inbox')
                // 👇 La logique du badge devient intelligente ici
                ->badge(function () use ($user, $employeId) {
                    $query = Conge::where('statut', 'en_attente');
                    
                    // Si ce n'est pas un admin, on ne compte que SES demandes
                    if ($user && !$user->hasRole('admin')) {
                        $query->where('employe_id', $employeId);
                    }
                    
                    return $query->count();
                })
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'en_attente')),

            'archives' => Tab::make('Archives')
                ->icon('heroicon-m-archive-box')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('statut', ['accepte', 'refuse'])),

            'tous' => Tab::make('Toutes les demandes')
                ->icon('heroicon-m-list-bullet'),
        ];
    }
}