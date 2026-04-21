<?php

namespace App\Filament\Widgets;

use App\Models\Conge;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AlertesBannerWidget extends Widget
{
    protected static string $view = 'filament.widgets.alertes-banner-widget';

    // Prend TOUTE la largeur
    protected int | string | array $columnSpan = 'full'; 

    // S'affiche TOUT EN HAUT
    protected static ?int $sort = -1; 

    public static function canView(): bool
    {
        $user = Auth::user();
        // S'affiche SEULEMENT s'il y a des congés en attente
        return $user && $user->hasRole('admin') && self::getCongesEnAttente() > 0;
    }

    public static function getCongesEnAttente(): int
    {
        return Conge::where('statut', 'en_attente')->count();
    }

    protected function getViewData(): array
    {
        return [
            'congesEnAttente' => self::getCongesEnAttente(),
        ];
    }
}