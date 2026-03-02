<?php

namespace App\Filament\Pages;

use App\Console\Commands\SendMonthlyReport;
use App\Models\AdministrativeAct;
use App\Models\Entity;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Página de informe mensual de actos administrativos.
 * Visible solo para super_admin y supervisor.
 */
class MonthlyReportPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Informe Mensual';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?string $title           = 'Informe Mensual de Actos Administrativos';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $slug             = 'monthly-report';

    protected static string $view = 'filament.pages.monthly-report';

    public int $selectedMonth;
    public int $selectedYear;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'supervisor']) ?? false;
    }

    public static function getNavigationUrl(): string
    {
        return url('/admin/monthly-report');
    }

    public function mount(): void
    {
        $this->selectedMonth = (int) Carbon::now()->month;
        $this->selectedYear  = (int) Carbon::now()->year;
    }

    // ── Datos del informe ─────────────────────────────────────────────────

    public function getStats(): array
    {
        $acts = AdministrativeAct::with([
                'organizationalUnit.entity',
                'documentarySeries',
                'documentarySubseries',
            ])
            ->whereNull('deleted_at')
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->get();

        $stats = [];
        foreach ($acts as $act) {
            $entity   = $act->organizationalUnit?->entity?->name  ?? 'Sin entidad';
            $unit     = $act->organizationalUnit?->name           ?? 'Sin unidad';
            $subserie = $act->documentarySubseries?->name
                ?? $act->documentarySeries?->name
                ?? 'Sin clasificación';

            $stats[$entity][$unit][$subserie] = ($stats[$entity][$unit][$subserie] ?? 0) + 1;
        }

        return $stats;
    }

    public function getTotalActs(): int
    {
        return AdministrativeAct::whereNull('deleted_at')
            ->whereYear('created_at', $this->selectedYear)
            ->whereMonth('created_at', $this->selectedMonth)
            ->count();
    }

    public function getPendingPdf()
    {
        return AdministrativeAct::with(['organizationalUnit.entity'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0');
            })
            ->orderBy('created_at')
            ->get();
    }

    public function getMonthLabel(): string
    {
        return Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)
            ->locale('es')
            ->isoFormat('MMMM YYYY');
    }

    // ── Acciones del encabezado ───────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeMonth')
                ->label('Cambiar período')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->form([
                    Select::make('month')
                        ->label('Mes')
                        ->options([
                            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
                            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
                            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
                            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                        ])
                        ->default($this->selectedMonth)
                        ->required(),
                    Select::make('year')
                        ->label('Año')
                        ->options(function () {
                            $years = [];
                            for ($y = 2024; $y <= now()->year + 1; $y++) {
                                $years[$y] = (string) $y;
                            }
                            return $years;
                        })
                        ->default($this->selectedYear)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->selectedMonth = (int) $data['month'];
                    $this->selectedYear  = (int) $data['year'];
                }),

            Action::make('sendReport')
                ->label('Enviar informe por correo')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Enviar informe mensual')
                ->modalDescription(fn () => "Se enviará el informe de {$this->getMonthLabel()} a todos los usuarios con rol super_admin y supervisor.")
                ->modalSubmitActionLabel('Enviar')
                ->action(function () {
                    Artisan::call('acts:monthly-report', [
                        '--month' => $this->selectedMonth,
                        '--year'  => $this->selectedYear,
                    ]);

                    Notification::make()
                        ->title('Informe enviado')
                        ->body("El informe de {$this->getMonthLabel()} fue enviado correctamente por correo.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
