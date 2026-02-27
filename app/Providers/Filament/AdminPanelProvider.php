<?php

namespace App\Providers\Filament;

use Awcodes\LightSwitch\LightSwitchPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Incluir Driver.js para tours de onboarding
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn(): string => '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>',
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => '<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script><script src="' . asset('js/tour-inventario.js') . '"></script>',
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->brandName('Inventario Documental')
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Amber,
            ])
            ->font('Inter')
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\CambiarPassword::class,
                \App\Filament\Pages\ImportErrors::class,
                \App\Filament\Pages\MonthlyReportPage::class,
                \App\Filament\Pages\ImportPermissionsPage::class,
            ])
            ->authenticatedRoutes(function (): void {
                // Registro con string literal para evitar carga de clase en tiempo de boot.
                // Resuelve RouteNotFoundException causado por OPcache o caché de Filament
                // que impide que el loop de páginas registre esta ruta correctamente.
                \Illuminate\Support\Facades\Route::name('pages.')
                    ->group(function (): void {
                        \Illuminate\Support\Facades\Route::get(
                            '/monthly-report',
                            \App\Filament\Pages\MonthlyReportPage::class
                        )->name('monthly-report');

                        \Illuminate\Support\Facades\Route::get(
                            '/import-permissions',
                            \App\Filament\Pages\ImportPermissionsPage::class
                        )->name('import-permissions');
                    });
            })
            ->userMenuItems([
                'cambiar-password' => \Filament\Navigation\MenuItem::make()
                    ->label('Cambiar Contraseña')
                    ->url(fn() => \App\Filament\Pages\CambiarPassword::getUrl())
                    ->icon('heroicon-o-key'),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // ->widgets([
            //     Widgets\AccountWidget::class,
            // ])
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
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                LightSwitchPlugin::make(),
            ]);
    }
}
