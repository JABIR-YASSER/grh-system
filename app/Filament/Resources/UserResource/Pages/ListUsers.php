<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'actifs' => Tab::make('Comptes Actifs')
                ->icon('heroicon-m-check-circle')
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat', 'actif')),

            'bloques' => Tab::make('Comptes Bloqués')
                ->icon('heroicon-m-x-circle')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('etat', 'bloque')),

            'tous' => Tab::make('Tous les utilisateurs')
                ->icon('heroicon-m-users'),
        ];
    }
}