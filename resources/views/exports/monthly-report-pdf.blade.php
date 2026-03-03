<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Mensual del Sistema Unificado de Registro — {{ mb_strtoupper($monthLabel) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5px;
            color: #374151;
            background: #ffffff;
            line-height: 1.45;
        }

        /* ════════════════════════════════════════════
           FRANJA BANDERA (gris - blanco - verde)
        ════════════════════════════════════════════ */
        .flag-stripe {
            width: 100%;
            height: 7px;
            background: #6b7280; /* gris - franja superior */
        }
        .flag-stripe-white {
            width: 100%;
            height: 7px;
            background: #f9fafb; /* blanco - franja central */
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }
        .flag-stripe-green {
            width: 100%;
            height: 7px;
            background: #1a5c38; /* verde - franja inferior */
        }

        /* ════════════════════════════════════════════
           HEADER INSTITUCIONAL
        ════════════════════════════════════════════ */
        .header-main {
            background: #1a5c38;
            color: #ffffff;
            padding: 16px 22px 14px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left {
            width: 58%;
            vertical-align: middle;
            padding-right: 16px;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .header-right {
            width: 42%;
            vertical-align: middle;
            padding-left: 16px;
            text-align: right;
        }
        .header-eyebrow {
            font-size: 7.5px;
            letter-spacing: 2px;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 4px;
        }
        .header-alcaldia {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .header-municipio {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #bbf7d0;
            margin-bottom: 2px;
        }
        .header-departamento {
            font-size: 8.5px;
            opacity: 0.75;
            letter-spacing: 0.3px;
        }
        .header-doc-category {
            font-size: 7.5px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 5px;
        }
        .header-doc-title {
            font-size: 12.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.3;
            margin-bottom: 8px;
        }
        .header-period {
            display: inline-block;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Sub-header (franja inferior del header) */
        .header-sub {
            background: #134d2e;
            padding: 5px 22px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .header-sub-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-sub-col {
            font-size: 7.5px;
            color: rgba(255,255,255,0.65);
            vertical-align: middle;
        }
        .header-sub-col strong {
            color: rgba(255,255,255,0.9);
        }
        .header-sub-divider {
            color: rgba(255,255,255,0.25);
            padding: 0 8px;
        }

        /* ════════════════════════════════════════════
           CUERPO DEL DOCUMENTO
        ════════════════════════════════════════════ */
        .body-content {
            padding: 16px 22px 10px;
        }

        /* ════════════════════════════════════════════
           KPI CARDS
        ════════════════════════════════════════════ */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            margin-bottom: 16px;
        }
        .kpi-card {
            width: 25%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 11px 9px 10px;
            text-align: center;
            vertical-align: top;
        }
        .kpi-card-primary { border-top: 3px solid #1a5c38; }
        .kpi-card-success  { border-top: 3px solid #16a34a; }
        .kpi-card-warning  { border-top: 3px solid #d97706; }
        .kpi-card-danger   { border-top: 3px solid #dc2626; }

        .kpi-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.0;
            margin-bottom: 2px;
        }
        .kpi-primary { color: #1a5c38; }
        .kpi-success { color: #16a34a; }
        .kpi-warning { color: #d97706; }
        .kpi-danger  { color: #dc2626; }
        .kpi-label {
            font-size: 7px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 700;
            margin: 5px 0 3px;
        }
        .kpi-rule {
            width: 28px;
            height: 1px;
            background: #d1d5db;
            margin: 0 auto 4px;
        }
        .kpi-sub {
            font-size: 7.5px;
            color: #9ca3af;
            line-height: 1.3;
        }

        /* ════════════════════════════════════════════
           SECCIONES
        ════════════════════════════════════════════ */
        .section { margin-bottom: 16px; }

        .section-header {
            background: #1a5c38;
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 5px 5px 0 0;
        }
        .section-header-gold   { background: #92400e; }
        .section-header-danger { background: #991b1b; }

        .section-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .section-badge {
            float: right;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 1px 9px;
            font-size: 7.5px;
            font-weight: 600;
        }
        .section-body {
            border: 1px solid #d1fae5;
            border-top: none;
            border-radius: 0 0 5px 5px;
            overflow: hidden;
        }
        .section-body-danger { border-color: #fecaca; }
        .section-body-gold   { border-color: #fde68a; }

        /* ════════════════════════════════════════════
           CUMPLIMIENTO POR UNIDAD
        ════════════════════════════════════════════ */
        .unit-row {
            padding: 8px 14px;
            border-bottom: 1px solid #f0fdf4;
            background: #ffffff;
        }
        .unit-row:nth-child(even) { background: #f8fdf9; }
        .unit-row:last-child { border-bottom: none; }

        .unit-name {
            font-size: 9px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 3px;
        }
        .unit-pct {
            float: right;
            font-size: 10px;
            font-weight: 700;
        }
        .unit-detail {
            font-size: 7.5px;
            color: #9ca3af;
        }
        .progress-track {
            background: #e5e7eb;
            border-radius: 3px;
            height: 6px;
            margin-top: 5px;
        }
        .progress-bar { height: 6px; border-radius: 3px; }
        .bar-success { background: #16a34a; }
        .bar-warning { background: #d97706; }
        .bar-danger  { background: #dc2626; }

        /* ════════════════════════════════════════════
           DISTRIBUCIÓN
        ════════════════════════════════════════════ */
        .dist-entity {
            background: #1a5c38;
            color: #ffffff;
            font-weight: 700;
            font-size: 9.5px;
            padding: 7px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .dist-entity-pill {
            float: right;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.3);
            color: #ffffff;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
        }
        .dist-unit {
            background: #f0fdf4;
            color: #166534;
            font-weight: 700;
            font-size: 9px;
            padding: 6px 14px 6px 26px;
            border-bottom: 1px solid #d1fae5;
        }
        .dist-unit-pill {
            float: right;
            background: #16a34a;
            color: #ffffff;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
        }
        .dist-sub {
            background: #ffffff;
            color: #374151;
            font-size: 8.5px;
            padding: 4px 14px 4px 40px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dist-sub:last-child { border-bottom: none; }
        .dist-sub-pill {
            float: right;
            background: #dcfce7;
            color: #166534;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 8px;
            font-weight: 700;
        }

        /* ════════════════════════════════════════════
           TABLA DE PENDIENTES
        ════════════════════════════════════════════ */
        table.pending-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        table.pending-table thead th {
            background: #991b1b;
            color: #ffffff;
            padding: 7px 9px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-size: 7.5px;
        }
        table.pending-table tbody tr { background: #ffffff; }
        table.pending-table tbody tr.row-overdue  { background: #fff5f5; }
        table.pending-table tbody tr.row-critical { background: #fff8f8; }
        table.pending-table tbody tr.row-warning  { background: #fffdf0; }
        table.pending-table tbody td {
            padding: 6px 9px;
            border-bottom: 1px solid #fee2e2;
            vertical-align: top;
        }
        table.pending-table tbody tr:last-child td { border-bottom: none; }

        /* ════════════════════════════════════════════
           BADGES
        ════════════════════════════════════════════ */
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 7.5px;
            font-weight: 700;
        }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-warning  { background: #fef3c7; color: #92400e; }
        .badge-info     { background: #dbeafe; color: #1e40af; }
        .badge-success  { background: #dcfce7; color: #166534; }

        /* ════════════════════════════════════════════
           RESUMEN DE ALERTAS
        ════════════════════════════════════════════ */
        .alert-summary {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 10px;
            font-size: 8.5px;
            color: #7f1d1d;
        }
        .alert-summary-item {
            display: inline-block;
            margin-right: 12px;
            font-weight: 600;
        }

        /* ════════════════════════════════════════════
           ESTADO CUMPLIDO (todo OK)
        ════════════════════════════════════════════ */
        .compliance-ok {
            padding: 18px;
            text-align: center;
            background: #f0fdf4;
            border-radius: 5px;
        }
        .compliance-ok-text {
            font-size: 11px;
            font-weight: 700;
            color: #16a34a;
            margin-bottom: 3px;
        }
        .compliance-ok-sub {
            font-size: 8.5px;
            color: #4ade80;
        }

        /* ════════════════════════════════════════════
           FOOTER
        ════════════════════════════════════════════ */
        .footer {
            margin-top: 20px;
            border-top: 2px solid #1a5c38;
            background: #f8fdf9;
            padding: 10px 22px 12px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-col {
            font-size: 7.5px;
            color: #6b7280;
            vertical-align: top;
            padding-right: 12px;
        }
        .footer-col:last-child { padding-right: 0; text-align: right; }
        .footer-label {
            font-weight: 700;
            color: #1a5c38;
            text-transform: uppercase;
            font-size: 7px;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .footer-confidential {
            display: inline-block;
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer-divider {
            border: none;
            border-top: 1px solid #d1fae5;
            margin: 7px 0;
        }

        /* ════════════════════════════════════════════
           UTILIDADES
        ════════════════════════════════════════════ */
        .clearfix::after { content: ''; display: table; clear: both; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; }
        .empty-state {
            padding: 18px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            font-size: 9px;
        }
        .page-break { page-break-before: always; }
        .no-break { page-break-inside: avoid; }

        @page {
            margin: 0;
            size: A4 portrait;
        }
    </style>
</head>
<body>

    {{-- ══════════════════════════════════════════════
         FRANJA BANDERA DE PUERTO BOYACÁ
    ══════════════════════════════════════════════ --}}
    <div class="flag-stripe"></div>
    <div class="flag-stripe-white"></div>
    <div class="flag-stripe-green"></div>

    {{-- ══════════════════════════════════════════════
         HEADER INSTITUCIONAL
    ══════════════════════════════════════════════ --}}
    <div class="header-main">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="header-eyebrow">Republica de Colombia</div>
                    <div class="header-alcaldia">Alcaldia Municipal</div>
                    <div class="header-municipio">Puerto Boyaca</div>
                    <div class="header-departamento">Boyaca &mdash; Colombia &nbsp;&bull;&nbsp; NIT 891.800.065-3</div>
                </td>
                <td class="header-right">
                    <div class="header-doc-category">Sistema de Inventario Documental</div>
                    <div class="header-doc-title">Informe de Gestion<br>Sistema Unificado de Registro</div>
                    <span class="header-period">{{ mb_strtoupper($monthLabel) }}</span>
                </td>
            </tr>
        </table>
    </div>
    <div class="header-sub">
        <table class="header-sub-table">
            <tr>
                <td class="header-sub-col">
                    <strong>Generado:</strong> {{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY, HH:mm') }}
                </td>
                <td class="header-sub-col" style="text-align:center;">
                    <strong>Dependencia:</strong> Secretaria General
                </td>
                <td class="header-sub-col" style="text-align:right;">
                    <strong>Clasificacion:</strong> Uso Interno &mdash; Restringido
                </td>
            </tr>
        </table>
    </div>

    <div class="body-content">

    {{-- ══════════════════════════════════════════════
         KPIs — INDICADORES CLAVE
    ══════════════════════════════════════════════ --}}
    <table class="kpi-table">
        <tr>
            {{-- Actos del período --}}
            <td class="kpi-card kpi-card-primary">
                <div class="kpi-value kpi-primary">{{ number_format($kpis['total']) }}</div>
                <div class="kpi-rule"></div>
                <div class="kpi-label">Actos del periodo</div>
                <div class="kpi-sub">{{ mb_strtoupper($monthLabel) }}</div>
            </td>

            {{-- Cumplimiento PDF --}}
            <td class="kpi-card {{ $kpis['compliance'] >= 90 ? 'kpi-card-success' : ($kpis['compliance'] >= 70 ? 'kpi-card-warning' : 'kpi-card-danger') }}">
                <div class="kpi-value {{ $kpis['compliance'] >= 90 ? 'kpi-success' : ($kpis['compliance'] >= 70 ? 'kpi-warning' : 'kpi-danger') }}">
                    {{ number_format($kpis['compliance'], 1) }}%
                </div>
                <div class="kpi-rule"></div>
                <div class="kpi-label">Cumplimiento PDF</div>
                <div class="kpi-sub">{{ number_format($kpis['conPdf']) }} con adjunto (historico)</div>
            </td>

            {{-- Sin PDF --}}
            <td class="kpi-card {{ $kpis['sinPdf'] > 0 ? 'kpi-card-warning' : 'kpi-card-success' }}">
                <div class="kpi-value {{ $kpis['sinPdf'] > 0 ? 'kpi-warning' : 'kpi-success' }}">
                    {{ number_format($kpis['sinPdf']) }}
                </div>
                <div class="kpi-rule"></div>
                <div class="kpi-label">Sin PDF adjunto</div>
                <div class="kpi-sub">{{ $kpis['sinPdf'] === 0 ? 'Totalmente al dia' : 'Requieren atencion' }}</div>
            </td>

            {{-- Vencidos --}}
            <td class="kpi-card {{ $kpis['vencidos'] > 0 ? 'kpi-card-danger' : 'kpi-card-success' }}">
                <div class="kpi-value {{ $kpis['vencidos'] > 0 ? 'kpi-danger' : 'kpi-success' }}">
                    {{ number_format($kpis['vencidos']) }}
                </div>
                <div class="kpi-rule"></div>
                <div class="kpi-label">Vencidos (&gt; 30 dias)</div>
                <div class="kpi-sub">
                    @if($kpis['vencidos'] === 0)
                        {{ $kpis['porVencer'] > 0 ? $kpis['porVencer'].' por vencer (7 dias)' : 'Sin vencidos' }}
                    @else
                        Atencion urgente requerida
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════
         CUMPLIMIENTO PDF POR UNIDAD
    ══════════════════════════════════════════════ --}}
    @if(count($complianceByUnit))
    <div class="section no-break">
        <div class="section-header clearfix">
            <span class="section-title">Cumplimiento PDF por unidad organizacional</span>
            <span class="section-badge">Historico acumulado</span>
        </div>
        <div class="section-body">
            @foreach($complianceByUnit as $item)
                @php $pct = $item['compliance']; @endphp
                <div class="unit-row clearfix">
                    <div class="unit-pct {{ $pct >= 90 ? 'kpi-success' : ($pct >= 70 ? 'kpi-warning' : 'kpi-danger') }}">
                        {{ number_format($pct, 1) }}%
                    </div>
                    <div class="unit-name">{{ $item['unit'] }}</div>
                    <div class="unit-detail">{{ $item['conPdf'] }} de {{ $item['total'] }} actos con PDF adjunto</div>
                    <div class="progress-track">
                        <div class="progress-bar {{ $pct >= 90 ? 'bar-success' : ($pct >= 70 ? 'bar-warning' : 'bar-danger') }}"
                             style="width: {{ min($pct, 100) }}%;"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════
         DISTRIBUCIÓN POR ENTIDAD / UNIDAD / SERIE
    ══════════════════════════════════════════════ --}}
    <div class="section">
        <div class="section-header clearfix">
            <span class="section-title">Distribucion por entidad, unidad y serie documental</span>
            <span class="section-badge">{{ mb_strtoupper($monthLabel) }}</span>
        </div>

        @if(empty($stats))
            <div class="section-body">
                <div class="empty-state">No se registraron entradas en el Sistema Unificado durante este periodo.</div>
            </div>
        @else
            <div class="section-body">
                @foreach($stats as $entityName => $units)
                    @php $entityTotal = array_sum(array_map('array_sum', $units)); @endphp
                    <div class="dist-entity clearfix">
                        {{ $entityName }}
                        <span class="dist-entity-pill">{{ number_format($entityTotal) }} actos</span>
                    </div>
                    @foreach($units as $unitName => $subseries)
                        @php $unitTotal = array_sum($subseries); @endphp
                        <div class="dist-unit clearfix">
                            {{ $unitName }}
                            <span class="dist-unit-pill">{{ number_format($unitTotal) }}</span>
                        </div>
                        @foreach($subseries as $subserieName => $count)
                            <div class="dist-sub clearfix">
                                {{ $subserieName }}
                                <span class="dist-sub-pill">{{ $count }}</span>
                            </div>
                        @endforeach
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         ACTOS SIN PDF — LISTADO DETALLADO
    ══════════════════════════════════════════════ --}}
    <div class="section page-break">
        <div class="section-header section-header-danger clearfix">
            <span class="section-title">Actos sin documento PDF adjunto &mdash; Historico</span>
            @if($pending->isNotEmpty())
                <span class="section-badge">{{ $pending->count() }} pendiente{{ $pending->count() !== 1 ? 's' : '' }}</span>
            @endif
        </div>

        @if($pending->isEmpty())
            <div class="section-body section-body clearfix">
                <div class="compliance-ok">
                    <div class="compliance-ok-text">CUMPLIMIENTO TOTAL</div>
                    <div class="compliance-ok-sub">Todos los registros del Sistema Unificado tienen documento PDF adjunto.</div>
                </div>
            </div>
        @else
            <div class="section-body section-body-danger">
                {{-- Resumen de alertas --}}
                @php
                    $nVencidos    = $pending->filter(fn($a) => $a->pdfDaysRemaining() < 0)->count();
                    $nCriticos    = $pending->filter(fn($a) => $a->pdfDaysRemaining() >= 0 && $a->pdfDaysRemaining() <= 5)->count();
                    $nAdvertencia = $pending->filter(fn($a) => $a->pdfDaysRemaining() > 5 && $a->pdfDaysRemaining() <= 15)->count();
                @endphp
                @if($nVencidos || $nCriticos || $nAdvertencia)
                <div style="padding: 8px 12px; background: #fef2f2; border-bottom: 1px solid #fecaca;">
                    <span style="font-size:8.5px; font-weight:700; color:#991b1b;">
                        Resumen de alertas:
                    </span>
                    @if($nVencidos)
                        <span style="font-size:8px; color:#991b1b; font-weight:600; margin-left:8px;">
                            {{ $nVencidos }} vencido{{ $nVencidos !== 1 ? 's' : '' }} (superaron 30 dias)
                        </span>
                    @endif
                    @if($nCriticos)
                        <span style="font-size:8px; color:#991b1b; font-weight:600; margin-left:8px;">
                            &bull; {{ $nCriticos }} critico{{ $nCriticos !== 1 ? 's' : '' }} (plazo &lt;= 5 dias)
                        </span>
                    @endif
                    @if($nAdvertencia)
                        <span style="font-size:8px; color:#92400e; font-weight:600; margin-left:8px;">
                            &bull; {{ $nAdvertencia }} por vencer (6&ndash;15 dias)
                        </span>
                    @endif
                </div>
                @endif

                {{-- Tabla detallada --}}
                <table class="pending-table">
                    <thead>
                        <tr>
                            <th style="width:11%;">Consecutivo</th>
                            <th style="width:20%;">Unidad / Entidad</th>
                            <th style="width:35%;">Objeto / Asunto del acto</th>
                            <th style="width:12%;">Fecha registro</th>
                            <th style="width:13%;" style="text-align:center;">Dias restantes</th>
                            <th style="width:9%;text-align:center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending->sortBy(fn($a) => $a->pdfDaysRemaining()) as $act)
                            @php
                                $days = $act->pdfDaysRemaining();
                                [$badgeCls, $statusLabel] = match(true) {
                                    $days < 0   => ['badge-danger',  'VENCIDO'],
                                    $days <= 5  => ['badge-danger',  'CRITICO'],
                                    $days <= 15 => ['badge-warning', 'PROXIMO'],
                                    default     => ['badge-info',    'VIGENTE'],
                                };
                                $rowCls = match(true) {
                                    $days < 0   => 'row-overdue',
                                    $days <= 5  => 'row-critical',
                                    $days <= 15 => 'row-warning',
                                    default     => '',
                                };
                                $daysLabel = $days < 0
                                    ? abs($days) . ' dias vencido'
                                    : ($days === 0 ? 'Vence hoy' : $days . ' dias restantes');
                            @endphp
                            <tr class="{{ $rowCls }}">
                                <td class="mono">{{ $act->filing_number }}</td>
                                <td>
                                    <span style="font-weight:600; color:#1e293b;">{{ $act->organizationalUnit?->name ?? '—' }}</span><br>
                                    <span style="font-size:7.5px; color:#9ca3af;">{{ $act->organizationalUnit?->entity?->name ?? '—' }}</span>
                                </td>
                                <td style="color:#374151;">{{ Str::limit($act->subject, 65) }}</td>
                                <td class="mono">
                                    {{ $act->created_at->format('d/m/Y') }}<br>
                                    <span style="font-size:7.5px; color:#9ca3af;">{{ $act->created_at->diffForHumans() }}</span>
                                </td>
                                <td style="text-align:center; font-size:8px; color:{{ $days < 0 ? '#991b1b' : ($days <= 5 ? '#b91c1c' : ($days <= 15 ? '#92400e' : '#374151')) }}; font-weight:600;">
                                    {{ $daysLabel }}
                                </td>
                                <td style="text-align:center;">
                                    <span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    </div>{{-- /body-content --}}

    {{-- ══════════════════════════════════════════════
         FOOTER INSTITUCIONAL
    ══════════════════════════════════════════════ --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="footer-col" style="width:33%;">
                    <div class="footer-label">Alcaldia Municipal de Puerto Boyaca</div>
                    Carrera 2 No. 10-21, Puerto Boyaca, Boyaca<br>
                    Tel: +57 (8) 738 33 00<br>
                    contactenos@puertoboyaca-boyaca.gov.co
                </td>
                <td class="footer-col" style="width:33%; text-align:center;">
                    <div class="footer-label">Informacion del documento</div>
                    NIT: 891.800.065-3<br>
                    Sistema de Inventario Documental<br>
                    Generado: {{ now()->format('d/m/Y H:i') }}
                </td>
                <td class="footer-col" style="width:33%; text-align:right; padding-right:0;">
                    <div class="footer-label">Clasificacion</div>
                    <span class="footer-confidential">Uso Interno &mdash; Restringido</span><br>
                    <span style="font-size:7px; color:#9ca3af; display:block; margin-top:3px;">
                        Documento generado automaticamente.<br>
                        No requiere firma para validez interna.
                    </span>
                </td>
            </tr>
        </table>
        <hr class="footer-divider">
        <div style="text-align:center; font-size:7px; color:#9ca3af;">
            www.puertoboyaca-boyaca.gov.co &nbsp;&bull;&nbsp; {{ now()->year }} &copy; Alcaldia Municipal de Puerto Boyaca &nbsp;&bull;&nbsp; Todos los derechos reservados
        </div>
    </div>

</body>
</html>
