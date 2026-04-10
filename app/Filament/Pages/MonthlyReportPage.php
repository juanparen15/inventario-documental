<?php

namespace App\Filament\Pages;

use App\Models\AdministrativeAct;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

/**
 * Informe mensual de actos administrativos.
 * Acceso: super_admin y supervisor.
 */
class MonthlyReportPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Informe Mensual';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?int    $navigationSort  = 10;
    protected static ?string $slug            = 'monthly-report';
    protected static string  $view            = 'filament.pages.monthly-report';

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

    public function getTitle(): string
    {
        if (!isset($this->selectedYear, $this->selectedMonth)) {
            return 'Informe Mensual';
        }
        return 'Informe Mensual — ' . mb_strtoupper($this->getMonthLabel());
    }

    public function mount(): void
    {
        $this->selectedMonth = (int) now()->month;
        $this->selectedYear  = (int) now()->year;
    }

    // ── Navegación de período ─────────────────────────────────────────────

    public function prevMonth(): void
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        if ($date->startOfMonth()->isFuture()) {
            return;
        }
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
    }

    // ── Datos para la vista (distribución y pendientes) ───────────────────

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
            $entity   = $act->organizationalUnit?->entity?->name ?? 'Sin entidad';
            $unit     = $act->organizationalUnit?->name          ?? 'Sin unidad';
            $subserie = $act->documentarySubseries?->name
                ?? $act->documentarySeries?->name
                ?? 'Sin clasificación';
            $stats[$entity][$unit][$subserie] = ($stats[$entity][$unit][$subserie] ?? 0) + 1;
        }

        return $stats;
    }

    public function getPendingPdf()
    {
        return AdministrativeAct::with(['organizationalUnit.entity'])
            ->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn($q) => $q->whereNull('confidential_attachments')->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'))
            ->orderBy('created_at')
            ->get();
    }

    public function getMonthLabel(): string
    {
        return Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)
            ->locale('es')
            ->isoFormat('MMMM YYYY');
    }

    public function isCurrentMonth(): bool
    {
        return $this->selectedMonth === (int) now()->month
            && $this->selectedYear  === (int) now()->year;
    }

    // ── URLs exportación ──────────────────────────────────────────────────

    public function getExcelUrl(): string
    {
        return route('monthly-report.excel', ['month' => $this->selectedMonth, 'year' => $this->selectedYear]);
    }

    // ── Acciones del encabezado ───────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('prevMonth')
                ->label('Anterior')
                ->icon('heroicon-o-chevron-left')
                ->color('gray')
                ->size('sm')
                ->action(fn () => $this->prevMonth()),

            Action::make('changeMonth')
                ->label(fn () => mb_strtoupper($this->getMonthLabel()))
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->size('sm')
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
                        ->options(fn () => collect(range(2024, now()->year))
                            ->mapWithKeys(fn ($y) => [$y => (string) $y])
                            ->all()
                        )
                        ->default($this->selectedYear)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->selectedMonth = (int) $data['month'];
                    $this->selectedYear  = (int) $data['year'];
                }),

            Action::make('nextMonth')
                ->label('Siguiente')
                ->icon('heroicon-o-chevron-right')
                ->iconPosition('after')
                ->color('gray')
                ->size('sm')
                ->disabled(fn () => $this->isCurrentMonth())
                ->action(fn () => $this->nextMonth()),

            Action::make('exportExcel')
                ->label('Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->size('sm')
                ->url(fn () => $this->getExcelUrl()),

            Action::make('sendReport')
                ->label('Enviar por correo')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->size('sm')
                ->requiresConfirmation()
                ->modalHeading('Enviar informe mensual')
                ->modalDescription(fn () => 'Se enviará el informe de ' . $this->getMonthLabel() . ' a todos los usuarios con rol super_admin y supervisor.')
                ->modalSubmitActionLabel('Enviar')
                ->action(function (): void {
                    Artisan::call('acts:monthly-report', [
                        '--month' => $this->selectedMonth,
                        '--year'  => $this->selectedYear,
                    ]);
                    Notification::make()
                        ->title('Informe enviado')
                        ->body('El informe de ' . $this->getMonthLabel() . ' fue enviado correctamente.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
