<?php

namespace App\Filament\Resources\EmployeResource\Pages;

use App\Filament\Resources\EmployeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Employe;
use App\Models\User;

class ListEmployes extends ListRecords
{
    protected static string $resource = EmployeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'actifs' => Tab::make('Effectif Actuel')
                ->icon('heroicon-m-user-group')
                ->badge(Employe::whereHas('user', fn($q) => $q->where('etat', 'actif'))->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn($q) => $q->where('etat', 'actif'))),

            'archives' => Tab::make('Anciens Employés / Archives')
                ->icon('heroicon-m-archive-box-x-mark')
                ->badge(Employe::whereHas('user', fn($q) => $q->where('etat', 'inactif'))->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn($q) => $q->where('etat', 'inactif'))),

            'tous' => Tab::make('Tout le personnel')
                ->icon('heroicon-m-users'),
        ];
    }
}