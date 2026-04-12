<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            
            ->darkMode(true)
            ->topNavigation() 
            ->globalSearch(false)
            ->brandName('GRH Workspace')
            ->brandLogo(asset('image/logo.png'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('image/logo.png'))
            
            ->font('Plus Jakarta Sans')
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Slate,
            ])
            
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // 1. Message de bienvenue
                Widgets\AccountWidget::class,
                
                // 2. Statistiques Rapides (Le nouveau widget !)
                \App\Filament\Widgets\StatsConge::class, 
                
                // 3. Les Alertes Administratives et Employés
                \App\Filament\Widgets\AlertesAdminWidget::class, 
                \App\Filament\Widgets\AlertesEmployeWidget::class,
                
                // 4. Les Graphiques Analytiques
                \App\Filament\Widgets\PointagesChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}