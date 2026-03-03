<?php

namespace App\Exports;

use App\Models\AdministrativeAct;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MonthlyReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $month,
        private readonly int $year,
    ) {}

    public function sheets(): array
    {
        return [
            new MonthlyReportResumenSheet($this->month, $this->year),
            new MonthlyReportUnidadesSheet($this->month, $this->year),
            new MonthlyReportSinPdfSheet($this->month, $this->year),
        ];
    }
}

// ─── Hoja 1: Resumen KPIs ────────────────────────────────────────────────────

class MonthlyReportResumenSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private array $kpis;
    private string $monthLabel;

    public function __construct(private readonly int $month, private readonly int $year)
    {
        $this->monthLabel = Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY');
        $this->kpis = $this->buildKpis();
    }

    public function title(): string { return 'Resumen'; }

    public function array(): array
    {
        $k = $this->kpis;

        return [
            ['INFORME MENSUAL DE ACTOS ADMINISTRATIVOS'],
            ['Período:', mb_strtoupper($this->monthLabel)],
            ['Generado:', now()->format('d/m/Y H:i')],
            [],
            ['INDICADORES CLAVE'],
            ['Indicador', 'Valor', 'Detalle'],
            ['Total de actos registrados', $k['total'], 'En el período seleccionado'],
            ['Actos con PDF adjunto', $k['conPdf'], number_format($k['compliance'], 1) . '% de cumplimiento'],
            ['Actos sin PDF adjunto', $k['sinPdf'], $k['sinPdf'] === 0 ? 'Sin pendientes' : 'Requieren atención'],
            ['Actos vencidos (> 30 días sin PDF)', $k['vencidos'], $k['vencidos'] === 0 ? 'Sin vencidos' : 'Urgente'],
            ['Por vencer (próximos 7 días)', $k['porVencer'], $k['porVencer'] === 0 ? 'Sin alertas' : 'Atención'],
            ['Tasa de cumplimiento PDF', number_format($k['compliance'], 1) . '%', $k['compliance'] >= 90 ? 'Óptimo' : ($k['compliance'] >= 70 ? 'Regular' : 'Crítico')],
        ];
    }

    private function buildKpis(): array
    {
        $noPdf = fn($q) => $q
            ->where(fn($i) => $i->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn($i) => $i->whereNull('confidential_attachments')->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'));

        $total    = AdministrativeAct::whereNull('deleted_at')->whereYear('created_at', $this->year)->whereMonth('created_at', $this->month)->count();
        $sinPdf   = $noPdf(AdministrativeAct::whereNull('deleted_at'))->count();
        $vencidos = $noPdf(AdministrativeAct::whereNull('deleted_at'))->where('created_at', '<=', now()->subDays(30))->count();
        $porVencer = $noPdf(AdministrativeAct::whereNull('deleted_at'))
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->where('created_at', '<=', now()->subDays(23)->endOfDay())
            ->count();

        return [
            'total'      => $total,
            'sinPdf'     => $sinPdf,
            'conPdf'     => $total - $sinPdf,
            'vencidos'   => $vencidos,
            'porVencer'  => $porVencer,
            'compliance' => $total > 0 ? round(($total - $sinPdf) / $total * 100, 1) : 100.0,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Título principal
                $sheet->mergeCells('A1:C1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1e40af']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Fila de período y fecha
                $sheet->getStyle('A2:B3')->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                // Encabezado de sección KPIs
                $sheet->mergeCells('A5:C5');
                $sheet->getStyle('A5')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1e40af']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Encabezados de columnas
                $sheet->getStyle('A6:C6')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '3b82f6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Datos con bordes
                $sheet->getStyle('A6:C12')->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
                ]);

                // Zebra striping
                foreach ([7, 9, 11] as $row) {
                    $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'f0f9ff']],
                    ]);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);
            },
        ];
    }
}

// ─── Hoja 2: Distribución por Unidad ────────────────────────────────────────

class MonthlyReportUnidadesSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    private string $monthLabel;

    public function __construct(private readonly int $month, private readonly int $year)
    {
        $this->monthLabel = Carbon::createFromDate($year, $month, 1)->locale('es')->isoFormat('MMMM YYYY');
    }

    public function title(): string { return 'Por Unidad'; }

    public function array(): array
    {
        $rows = [
            ['DISTRIBUCIÓN DE ACTOS — ' . mb_strtoupper($this->monthLabel)],
            [],
            ['Entidad', 'Unidad Organizacional', 'Serie / Subserie', 'Cantidad de Actos'],
        ];

        $acts = AdministrativeAct::with(['organizationalUnit.entity', 'documentarySeries', 'documentarySubseries'])
            ->whereNull('deleted_at')
            ->whereYear('created_at', $this->year)
            ->whereMonth('created_at', $this->month)
            ->get();

        $grouped = [];
        foreach ($acts as $act) {
            $entity   = $act->organizationalUnit?->entity?->name ?? 'Sin entidad';
            $unit     = $act->organizationalUnit?->name ?? 'Sin unidad';
            $subserie = $act->documentarySubseries?->name ?? $act->documentarySeries?->name ?? 'Sin clasificación';
            $grouped[$entity][$unit][$subserie] = ($grouped[$entity][$unit][$subserie] ?? 0) + 1;
        }

        foreach ($grouped as $entity => $units) {
            foreach ($units as $unit => $subseries) {
                foreach ($subseries as $subserie => $count) {
                    $rows[] = [$entity, $unit, $subserie, $count];
                }
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:D1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '1e40af']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A3:D3')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '3b82f6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 3) {
                    $sheet->getStyle("A3:D{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
                    ]);
                }
                $sheet->getRowDimension(1)->setRowHeight(24);
            },
        ];
    }
}

// ─── Hoja 3: Actos Sin PDF ───────────────────────────────────────────────────

class MonthlyReportSinPdfSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function __construct(private readonly int $month, private readonly int $year) {}

    public function title(): string { return 'Sin PDF'; }

    public function array(): array
    {
        $rows = [
            ['ACTOS SIN PDF ADJUNTO (HISTÓRICO)'],
            ['Generado:', now()->format('d/m/Y H:i')],
            [],
            ['Consecutivo', 'Entidad', 'Unidad', 'Objeto / Asunto', 'Fecha Registro', 'Días para PDF', 'Estado'],
        ];

        $pending = AdministrativeAct::with(['organizationalUnit.entity'])
            ->whereNull('deleted_at')
            ->where(fn($q) => $q->whereNull('attachments')->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn($q) => $q->whereNull('confidential_attachments')->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'))
            ->orderBy('created_at')
            ->get();

        foreach ($pending as $act) {
            $days = $act->pdfDaysRemaining();
            if ($days < 0) {
                $estado = 'VENCIDO';
                $diasLabel = 'Vencido hace ' . abs($days) . 'd';
            } elseif ($days <= 5) {
                $estado = 'CRÍTICO';
                $diasLabel = $days . ' días';
            } elseif ($days <= 15) {
                $estado = 'ADVERTENCIA';
                $diasLabel = $days . ' días';
            } else {
                $estado = 'En plazo';
                $diasLabel = $days . ' días';
            }

            $rows[] = [
                $act->filing_number,
                $act->organizationalUnit?->entity?->name ?? '—',
                $act->organizationalUnit?->name ?? '—',
                $act->subject,
                $act->created_at->format('d/m/Y'),
                $diasLabel,
                $estado,
            ];
        }

        if (count($rows) === 4) {
            $rows[] = ['', '', '', '✓ Todos los actos tienen PDF adjunto.', '', '', ''];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:G1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'dc2626']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A4:G4')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'ef4444']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $lastRow = $sheet->getHighestRow();
                if ($lastRow > 4) {
                    $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e5e7eb']]],
                    ]);
                }
                $sheet->getRowDimension(1)->setRowHeight(24);
            },
        ];
    }
}
