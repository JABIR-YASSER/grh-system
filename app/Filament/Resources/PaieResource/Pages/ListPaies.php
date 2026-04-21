<?php

namespace App\Filament\Resources\PaieResource\Pages;

use App\Filament\Resources\PaieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Paie;
use Illuminate\Support\Facades\Auth; // 👈 Import essentiel ici aussi

class ListPaies extends ListRecords
{
    protected static string $resource = PaieResource::class;

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
            'en_attente' => Tab::make('Paies en attente')
                ->icon('heroicon-m-clock')
                // 👇 Le badge devient "intelligent"
                ->badge(function () use ($user, $employeId) {
                    $query = Paie::where('statut', 'en_attente');
                    
                    // Si ce n'est pas l'admin, on filtre par son ID employé
                    if ($user && !$user->hasRole('admin')) {
                        $query->where('employe_id', $employeId);
                    }
                    
                    return $query->count();
                })
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'en_attente')),

            'payees' => Tab::make('Historique des paiements')
                ->icon('heroicon-m-check-badge')
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut', 'paye')),

            'toutes' => Tab::make('Toutes les fiches')
                ->icon('heroicon-m-list-bullet'),
        ];
    }
}