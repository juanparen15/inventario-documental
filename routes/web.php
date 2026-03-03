<?php

use App\Exports\MonthlyReportExport;
use App\Models\AdministrativeAct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::get('/', function () {
    return view('welcome');
});

// ── Exportaciones del Informe Mensual ──────────────────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/monthly-report/download/excel', function (Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'supervisor']), 403);

        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        $label = Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM-YYYY');

        return Excel::download(
            new MonthlyReportExport($month, $year),
            "informe-mensual-{$label}.xlsx"
        );
    })->name('monthly-report.excel');

    Route::get('/admin/monthly-report/download/pdf', function (Request $request) {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'supervisor']), 403);

        $month = (int) $request->get('month', now()->month);
        $year  = (int) $request->get('year', now()->year);
        $label = Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM-YYYY');

        $noPdf = fn($q) => $q
            ->where(fn($i) => $i->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn($i) => $i->whereNull('confidential_attachments')->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'));

        $total      = AdministrativeAct::whereNull('deleted_at')->whereYear('created_at', $year)->whereMonth('created_at', $month)->count();
        $sinPdf     = $noPdf(AdministrativeAct::whereNull('deleted_at'))->count();
        $vencidos   = $noPdf(AdministrativeAct::whereNull('deleted_at'))->where('created_at', '<=', now()->subDays(30))->count();
        $porVencer  = $noPdf(AdministrativeAct::whereNull('deleted_at'))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->where('created_at', '<=', now()->subDays(23)->endOfDay())
            ->count();

        $kpis = [
            'total'      => $total,
            'conPdf'     => $total - $sinPdf,
            'sinPdf'     => $sinPdf,
            'vencidos'   => $vencidos,
            'porVencer'  => $porVencer,
            'compliance' => $total > 0 ? round(($total - $sinPdf) / $total * 100, 1) : 100.0,
        ];

        // Distribución por entidad/unidad/serie
        $acts = AdministrativeAct::with(['organizationalUnit.entity', 'documentarySeries', 'documentarySubseries'])
            ->whereNull('deleted_at')->whereYear('created_at', $year)->whereMonth('created_at', $month)->get();

        $stats = [];
        foreach ($acts as $act) {
            $entity   = $act->organizationalUnit?->entity?->name ?? 'Sin entidad';
            $unit     = $act->organizationalUnit?->name          ?? 'Sin unidad';
            $subserie = $act->documentarySubseries?->name ?? $act->documentarySeries?->name ?? 'Sin clasificación';
            $stats[$entity][$unit][$subserie] = ($stats[$entity][$unit][$subserie] ?? 0) + 1;
        }

        // Cumplimiento por unidad
        $allActs = AdministrativeAct::with('organizationalUnit')->whereNull('deleted_at')->get();
        $byUnit = [];
        foreach ($allActs as $act) {
            $unit = $act->organizationalUnit?->name ?? 'Sin unidad';
            $byUnit[$unit]['total'] = ($byUnit[$unit]['total'] ?? 0) + 1;
            if (!$act->lacksPdf()) {
                $byUnit[$unit]['conPdf'] = ($byUnit[$unit]['conPdf'] ?? 0) + 1;
            }
        }
        $complianceByUnit = collect($byUnit)->map(fn($data, $unit) => [
            'unit'       => $unit,
            'total'      => $data['total'],
            'conPdf'     => $data['conPdf'] ?? 0,
            'compliance' => $data['total'] > 0 ? round((($data['conPdf'] ?? 0) / $data['total']) * 100, 1) : 100.0,
        ])->sortByDesc('total')->values()->all();

        $pending = $noPdf(AdministrativeAct::with(['organizationalUnit.entity'])->whereNull('deleted_at'))->orderBy('created_at')->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.monthly-report-pdf', [
            'monthLabel'       => Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY'),
            'kpis'             => $kpis,
            'stats'            => $stats,
            'complianceByUnit' => $complianceByUnit,
            'pending'          => $pending,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("informe-mensual-{$label}.pdf");
    })->name('monthly-report.pdf');

});
