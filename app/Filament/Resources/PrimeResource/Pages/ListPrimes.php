<?php

namespace App\Filament\Resources\PrimeResource\Pages;

use App\Filament\Resources\PrimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Prime;

class ListPrimes extends ListRecords
{
    protected static string $resource = PrimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'a_payer' => Tab::make('À verser')
                ->icon('heroicon-m-banknotes')
                ->badge(Prime::where('payee', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payee', false)),

            'payees' => Tab::make('Historique des versements')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('payee', true)),

            'tous' => Tab::make('Toutes les primes')
                ->icon('heroicon-m-list-bullet'),
        ];
    }
}