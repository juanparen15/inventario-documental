<?php

namespace App\Providers\Filament;

use Awcodes\LightSwitch\LightSwitchPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use JeffersonGoncalves\Filament\WhatsappWidget\WhatsappWidgetPlugin;
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
            fn(): string => '<script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script><script src="' . asset('js/tour-inventario.js') . '"></script><script src="' . asset('js/chart-config.js') . '"></script>',
        );

        // Chatwoot — widget de chat
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): string => <<<'HTML'
            <script>
              window.chatwootSettings = {"position":"right","type":"standard","launcherTitle":""};
              (function(d,t) {
                var BASE_URL="https://contactenos.ticsistemas.com.co";
                var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
                g.src=BASE_URL+"/packs/js/sdk.js";
                g.async = true;
                s.parentNode.insertBefore(g,s);
                g.onload=function(){
                  window.chatwootSDK.run({
                    websiteToken: '47cdrW9PfPVat8DrYCDNMoQk',
                    baseUrl: BASE_URL
                  })
                }
              })(document,"script");
            </script>
            HTML,
        );

        // WhatsApp Widget — CSS
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn(): string => '<link rel="stylesheet" href="' . asset('vendor/whatsapp-widget/assets/app-CgZ3I7dV.css') . '">'
                . '<style>.bottom-right{right:auto!important;left:30px!important}</style>',
        );

        // WhatsApp Widget — HTML + JS
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): \Illuminate\Contracts\View\View => view('whatsapp-widget::whatsapp-widget-body'),
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
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\CambiarPassword::class,
                \App\Filament\Pages\ImportErrors::class,
                \App\Filament\Pages\MonthlyReportPage::class,
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
                WhatsappWidgetPlugin::make(),
            ]);
    }
}
