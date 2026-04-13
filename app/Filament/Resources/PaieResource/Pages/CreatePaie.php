<?php

namespace App\Filament\Resources\PaieResource\Pages;

use App\Filament\Resources\PaieResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaie extends CreateRecord
{
    protected static string $resource = PaieResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function afterCreate(): void
    {
        // On récupère la paie qui vient d'être créée
        $paie = $this->record;

        // On cherche toutes les primes non payées de cet employé...
        \App\Models\Prime::where('employe_id', $paie->employe_id)
            ->where('payee', false)
            // ... et on les marque comme payées !
            ->update(['payee' => true]);
    }
}
