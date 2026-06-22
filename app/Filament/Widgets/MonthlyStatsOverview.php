<?php

namespace App\Filament\Widgets;

use App\Models\AdministrativeAct;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPIs del informe mensual.
 * No aparece en el dashboard — solo se embebe en MonthlyReportPage.
 */
class MonthlyStatsOverview extends BaseWidget
{
    public int $month;
    public int $year;

    protected static ?string $pollingInterval = null;
    protected static bool    $isLazy          = false;

    public static function canView(): bool { return false; }

    protected function getStats(): array
    {
        $noPdf = fn($q) => $q
            ->where(fn($i) => $i->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn($i) => $i->whereNull('confidential_attachments')->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'));

        // Actos del período seleccionado
        $totalPeriod = AdministrativeAct::whereNull('deleted_at')
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->count();

        // Comparación con mes anterior
        $prev        = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $prevTotal   = AdministrativeAct::whereNull('deleted_at')
            ->whereYear('created_at', $prev->year)
            ->whereMonth('created_at', $prev->month)
            ->count();
        $trend = $totalPeriod - $prevTotal;

        // Histórico global para compliance
        $totalAll   = AdministrativeAct::whereNull('deleted_at')->count();
        $sinPdf     = $noPdf(AdministrativeAct::whereNull('deleted_at'))->count();
        $vencidos   = $noPdf(AdministrativeAct::whereNull('deleted_at'))
            ->where('created_at', '<=', now()->subDays(30))
            ->count();
        $porVencer  = $noPdf(AdministrativeAct::whereNull('deleted_at'))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->where('created_at', '<=', now()->subDays(23)->endOfDay())
            ->count();

        $compliance = $totalAll > 0
            ? round(($totalAll - $sinPdf) / $totalAll * 100, 1)
            : 100.0;

        // Documentos anulados durante el período (por fecha de anulación = deleted_at)
        $anulados = AdministrativeAct::onlyTrashed()
            ->whereYear('deleted_at', $this->year)
            ->whereMonth('deleted_at', $this->month)
            ->count();

        // Mini chart: últimos 7 meses de actos del período (solo ese mes por año)
        $trendChart = collect(range(6, 0))->map(fn($i) => (int) AdministrativeAct::whereNull('deleted_at')
            ->whereYear('created_at', now()->subMonths($i)->year)
            ->whereMonth('created_at', now()->subMonths($i)->month)
            ->count()
        )->all();

        return [
            // ── Actos del período ────────────────────────────────────────
            Stat::make('Documentos del período', number_format($totalPeriod))
                ->description(match (true) {
                    $trend > 0  => '+' . $trend . ' vs ' . $prev->locale('es')->isoFormat('MMMM'),
                    $trend < 0  => $trend . ' vs ' . $prev->locale('es')->isoFormat('MMMM'),
                    default     => 'Igual que ' . $prev->locale('es')->isoFormat('MMMM'),
                })
                ->descriptionIcon(match (true) {
                    $trend > 0  => 'heroicon-m-arrow-trending-up',
                    $trend < 0  => 'heroicon-m-arrow-trending-down',
                    default     => 'heroicon-m-minus',
                })
                ->color(match (true) {
                    $trend > 0  => 'success',
                    $trend < 0  => 'danger',
                    default     => 'gray',
                })
                ->chart($trendChart),

            // ── Cumplimiento PDF ─────────────────────────────────────────
            Stat::make('Cumplimiento PDF', $compliance . '%')
                ->description($sinPdf === 0
                    ? 'Todos los documentos tienen PDF'
                    : number_format($sinPdf) . ' pendiente(s) en total'
                )
                ->descriptionIcon($sinPdf === 0
                    ? 'heroicon-m-check-circle'
                    : 'heroicon-m-exclamation-triangle'
                )
                ->color(match (true) {
                    $compliance >= 90 => 'success',
                    $compliance >= 70 => 'warning',
                    default           => 'danger',
                }),

            // ── Sin PDF ──────────────────────────────────────────────────
            Stat::make('Sin PDF adjunto', number_format($sinPdf))
                ->description($sinPdf === 0
                    ? 'Sin pendientes'
                    : 'Requieren atención'
                )
                ->descriptionIcon($sinPdf === 0
                    ? 'heroicon-m-check-circle'
                    : 'heroicon-m-document-minus'
                )
                ->color($sinPdf === 0 ? 'success' : 'warning'),

            // ── Vencidos ─────────────────────────────────────────────────
            Stat::make('Vencidos (> 30 días)', number_format($vencidos))
                ->description($vencidos === 0
                    ? ($porVencer > 0 ? $porVencer . ' por vencer esta semana' : 'Sin documentos vencidos')
                    : 'Superaron el plazo sin PDF'
                )
                ->descriptionIcon($vencidos === 0
                    ? ($porVencer > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                    : 'heroicon-m-exclamation-circle'
                )
                ->color($vencidos === 0
                    ? ($porVencer > 0 ? 'warning' : 'success')
                    : 'danger'
                ),

            // ── Anulados del período ─────────────────────────────────────
            Stat::make('Documentos anulados', number_format($anulados))
                ->description($anulados === 0
                    ? 'Sin anulaciones este mes'
                    : 'Anulados durante el período'
                )
                ->descriptionIcon($anulados === 0
                    ? 'heroicon-m-check-circle'
                    : 'heroicon-m-no-symbol'
                )
                ->color($anulados === 0 ? 'success' : 'danger'),
        ];
    }
}
