<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatwootSearchController extends Controller
{
    /**
     * Endpoint combinado: búsqueda + estadísticas en una sola llamada.
     * Filtra la información según el rol del usuario que consulta.
     *
     * GET /api/chatwoot/context?q=mensaje&email=user@ejemplo.com&token=...
     *
     * Roles con acceso total : super_admin, supervisor
     * Roles con acceso parcial: cualquier otro rol → solo su entidad
     */
    public function context(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rawQuery = trim($request->query('q', ''));
        $email    = trim($request->query('email', ''));
        $keywords = $this->extractKeywords($rawQuery);

        // ── Resolver permisos del usuario ────────────────────────────
        $accessLevel  = 'full';   // super_admin / supervisor
        $entityId     = null;
        $entityName   = null;
        $unitId       = null;
        $unitName     = null;

        if ($email) {
            $user = User::with('organizationalUnit.entity')
                ->where('email', $email)
                ->first();

            if ($user) {
                $isPrivileged = $user->hasAnyRole(['super_admin', 'supervisor']);

                if (! $isPrivileged) {
                    $accessLevel = 'restricted';
                    $unit        = $user->organizationalUnit;
                    $unitId      = $unit?->id;
                    $unitName    = $unit?->name;
                    $entityId    = $unit?->entity?->id;
                    $entityName  = $unit?->entity?->name;
                }
                // else: super_admin / supervisor → accessLevel permanece 'full'
            } else {
                // Email recibido pero no existe en la BD → usuario desconocido.
                // Restringir sin entidad: no se devuelve ningún registro.
                $accessLevel = 'restricted';
                $entityName  = 'Desconocido';
            }
        } else {
            // Sin email (contacto anónimo) → también restringido sin datos.
            $accessLevel = 'restricted';
            $entityName  = 'Anónimo';
        }

        $filter = $accessLevel === 'restricted'
            ? ['unit_id' => $unitId, 'entity_id' => $entityId]
            : null;

        // ── Búsqueda y estadísticas ───────────────────────────────────
        $searchData = [];
        $statsData  = [];

        if (! empty($keywords)) {
            $searchData = [
                'keywords'  => $keywords,
                'sur'       => $this->searchSUR($keywords, 5, $filter),
                'inventory' => $this->searchInventory($keywords, 5, $filter),
            ];
            $statsData = $this->buildStats($keywords, $filter);
        }

        // Totales: globales para admins, de la entidad para usuarios normales
        $globalTotals = [
            'sur_total'        => $this->countSUR($filter),
            'inventario_total' => $this->countInventory($filter),
        ];

        return response()->json([
            'pregunta'         => $rawQuery,
            'keywords'         => $keywords,
            'access_level'     => $accessLevel,
            '_debug_email'     => $email,   // temporal — quitar después
            'scope'            => $accessLevel === 'restricted'
                ? ['entidad' => $entityName, 'dependencia' => $unitName]
                : ['entidad' => 'Todas', 'dependencia' => 'Todas'],
            'totales_globales' => $globalTotals,
            'busqueda'         => $searchData,
            'estadisticas'     => $statsData,
        ]);
    }

    /**
     * Endpoint de búsqueda para el bot de Chatwoot.
     * Extrae palabras clave del mensaje completo del usuario y busca
     * en SUR (Actos Administrativos) e Inventario Documental (FUID).
     *
     * GET /api/chatwoot/search?q=texto&limit=5
     */
    public function search(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $rawQuery = trim($request->query('q', ''));
        $limit    = min((int) $request->query('limit', 5), 10);

        // Extraer palabras útiles (≥4 chars, sin stopwords)
        $keywords = $this->extractKeywords($rawQuery);

        if (empty($keywords)) {
            return response()->json([
                'query'    => $rawQuery,
                'keywords' => [],
                'results'  => [],
                'summary'  => 'No se encontraron términos de búsqueda relevantes.',
            ]);
        }

        $surResults       = $this->searchSUR($keywords, $limit);
        $inventoryResults = $this->searchInventory($keywords, $limit);
        $total            = count($surResults) + count($inventoryResults);

        $summary = $total > 0
            ? "Se encontraron {$total} registro(s) para los términos: " . implode(', ', $keywords) . '.'
            : 'No se encontraron registros para los términos: ' . implode(', ', $keywords) . '.';

        return response()->json([
            'query'     => $rawQuery,
            'keywords'  => $keywords,
            'summary'   => $summary,
            'sur'       => $surResults,
            'inventory' => $inventoryResults,
        ]);
    }

    /**
     * Endpoint de estadísticas/conteos para el bot de Chatwoot.
     * Devuelve totales agrupados por entidad y serie en SUR e Inventario.
     *
     * GET /api/chatwoot/stats?entity=secretaria+general
     */
    public function stats(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $filterRaw = trim($request->query('entity', ''));
        $keywords  = $filterRaw ? $this->extractKeywords($filterRaw) : [];

        // ── SUR: conteos por entidad ──────────────────────────────────
        $surQuery = AdministrativeAct::with('organizationalUnit.entity')
            ->selectRaw('COUNT(*) as total, organizational_unit_id')
            ->whereNull('deleted_at')
            ->groupBy('organizational_unit_id');

        $surByEntity = AdministrativeAct::with('organizationalUnit.entity')
            ->whereNull('deleted_at')
            ->when($keywords, function ($q) use ($keywords) {
                $q->whereHas('organizationalUnit.entity', function ($e) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $e->orWhere('name', 'like', "%{$kw}%");
                    }
                });
            })
            ->get()
            ->groupBy(fn($a) => $a->organizationalUnit?->entity?->name ?? 'Sin entidad')
            ->map(fn($group, $name) => [
                'entidad' => $name,
                'total'   => $group->count(),
                'con_pdf' => $group->filter(fn($a) => ! $a->lacksPdf())->count(),
                'sin_pdf' => $group->filter(fn($a) => $a->lacksPdf())->count(),
            ])
            ->sortByDesc('total')
            ->values();

        // ── Inventario: conteos por entidad ───────────────────────────
        $invByEntity = InventoryRecord::with('organizationalUnit.entity')
            ->whereNull('deleted_at')
            ->when($keywords, function ($q) use ($keywords) {
                $q->whereHas('organizationalUnit.entity', function ($e) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $e->orWhere('name', 'like', "%{$kw}%");
                    }
                });
            })
            ->get()
            ->groupBy(fn($r) => $r->organizationalUnit?->entity?->name ?? 'Sin entidad')
            ->map(fn($group, $name) => [
                'entidad' => $name,
                'total'   => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        // ── Totales globales ──────────────────────────────────────────
        $surTotal = AdministrativeAct::whereNull('deleted_at')->count();
        $invTotal = InventoryRecord::whereNull('deleted_at')->count();

        return response()->json([
            'filter'    => $filterRaw ?: 'todos',
            'keywords'  => $keywords,
            'totales'   => [
                'sur_total'       => $surTotal,
                'inventario_total' => $invTotal,
            ],
            'sur_por_entidad'       => $surByEntity,
            'inventario_por_entidad' => $invByEntity,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function buildStats(array $keywords, ?array $filter = null): array
    {
        $surByEntity = AdministrativeAct::with('organizationalUnit.entity')
            ->whereNull('deleted_at')
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->when($keywords, function ($q) use ($keywords) {
                $q->whereHas('organizationalUnit.entity', function ($e) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $e->orWhere('name', 'like', "%{$kw}%");
                    }
                });
            })
            ->get()
            ->groupBy(fn($a) => $a->organizationalUnit?->entity?->name ?? 'Sin entidad')
            ->map(fn($group, $name) => [
                'entidad' => $name,
                'total'   => $group->count(),
                'con_pdf' => $group->filter(fn($a) => ! $a->lacksPdf())->count(),
                'sin_pdf' => $group->filter(fn($a) => $a->lacksPdf())->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $invByEntity = InventoryRecord::with('organizationalUnit.entity')
            ->whereNull('deleted_at')
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->when($keywords, function ($q) use ($keywords) {
                $q->whereHas('organizationalUnit.entity', function ($e) use ($keywords) {
                    foreach ($keywords as $kw) {
                        $e->orWhere('name', 'like', "%{$kw}%");
                    }
                });
            })
            ->get()
            ->groupBy(fn($r) => $r->organizationalUnit?->entity?->name ?? 'Sin entidad')
            ->map(fn($group, $name) => [
                'entidad' => $name,
                'total'   => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return [
            'sur_por_entidad'        => $surByEntity,
            'inventario_por_entidad' => $invByEntity,
        ];
    }

    private function countSUR(?array $filter): int
    {
        return AdministrativeAct::whereNull('deleted_at')
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->count();
    }

    private function countInventory(?array $filter): int
    {
        return InventoryRecord::whereNull('deleted_at')
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->count();
    }

    private function authorized(Request $request): bool
    {
        $token = $request->header('X-Chatwoot-Token') ?? $request->query('token');
        return $token === config('app.chatwoot_api_token');
    }

    /**
     * Extrae palabras clave útiles de un texto largo.
     * Elimina stopwords en español e inglés y palabras cortas (<4 chars).
     */
    private function extractKeywords(string $text): array
    {
        $stopwords = [
            'para', 'como', 'desde', 'hasta', 'cuando', 'donde', 'sobre',
            'entre', 'durante', 'mediante', 'según', 'cuántos', 'cuantos',
            'cuántas', 'cuantas', 'tiene', 'tienen', 'hay', 'haber', 'saber',
            'quiero', 'necesito', 'puedes', 'puede', 'dame', 'dime', 'muestra',
            'mostrar', 'buscar', 'busco', 'lista', 'listar', 'total', 'todos',
            'todas', 'unos', 'unas', 'este', 'esta', 'estos', 'estas', 'ese',
            'esa', 'esos', 'esas', 'cual', 'cuál', 'que', 'qué', 'una', 'uno',
            'los', 'las', 'del', 'con', 'por', 'son', 'fue', 'ser', 'sus',
            'registro', 'registros', 'unificado', 'sistema', 'inventario',
            'documental', 'documentos', 'documento', 'alcaldia', 'alcaldía',
            'información', 'informacion', 'consulta', 'quieres', 'decir',
            'favor', 'gracias', 'hola', 'buenos', 'días', 'dias', 'tardes',
        ];

        // Normalizar: minúsculas, quitar tildes, extraer palabras
        $normalized = mb_strtolower($text);
        $normalized = str_replace(
            ['á','é','í','ó','ú','ü','ñ'],
            ['a','e','i','o','u','u','n'],
            $normalized
        );
        preg_match_all('/\b[a-záéíóúüñ]{4,}\b/u', $normalized, $matches);
        $words = $matches[0] ?? [];

        $keywords = array_values(array_unique(
            array_filter($words, fn($w) => ! in_array($w, $stopwords))
        ));

        return array_slice($keywords, 0, 5); // máx 5 keywords
    }

    // ──────────────────────────────────────────────────────────────
    // Búsqueda SUR
    // ──────────────────────────────────────────────────────────────

    private function searchSUR(array $keywords, int $limit, ?array $filter = null): array
    {
        $acts = AdministrativeAct::with([
            'organizationalUnit.entity',
            'documentarySeries',
            'documentarySubseries',
            'actClassification',
            'creator',
            'updater',
        ])
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('filing_number', 'like', "%{$kw}%")
                      ->orWhere('subject', 'like', "%{$kw}%")
                      ->orWhereHas('documentarySeries', fn($s) => $s->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('documentarySubseries', fn($s) => $s->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('organizationalUnit', fn($u) => $u->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('organizationalUnit.entity', fn($e) => $e->where('name', 'like', "%{$kw}%"));
                }
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $acts->map(fn(AdministrativeAct $act) => [
            'consecutivo'          => $act->filing_number,
            'asunto'               => $act->subject,
            'vigencia'             => $act->vigencia,
            'serie'                => $act->documentarySeries?->name,
            'subserie'             => $act->documentarySubseries?->name,
            'entidad'              => $act->organizationalUnit?->entity?->name,
            'dependencia'          => $act->organizationalUnit?->name,
            'clasificacion'        => $act->actClassification?->name,
            'tiene_pdf'            => ! $act->lacksPdf(),
            'tiene_confidencial'   => ! empty($act->confidential_attachments),
            'folios'               => $act->folios,
            'notas'                => $act->notes,
            'razon_retraso_pdf'    => $act->late_upload_reason,
            'creado_por'           => $act->creator?->name,
            'fecha_creacion'       => $act->created_at?->format('d/m/Y H:i'),
            'actualizado_por'      => $act->updater?->name,
            'fecha_actualizacion'  => $act->updated_at?->format('d/m/Y H:i'),
        ])->toArray();
    }

    // ──────────────────────────────────────────────────────────────
    // Búsqueda Inventario
    // ──────────────────────────────────────────────────────────────

    private function searchInventory(array $keywords, int $limit, ?array $filter = null): array
    {
        $records = InventoryRecord::with([
            'organizationalUnit.entity',
            'documentarySeries',
            'documentarySubseries',
            'storageMedium',
            'priorityLevel',
            'creator',
            'updater',
        ])
            ->when($filter, fn($q) => $q->whereHas('organizationalUnit',
                fn($u) => $u->where('entity_id', $filter['entity_id'])))
            ->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    $q->orWhere('title', 'like', "%{$kw}%")
                      ->orWhere('description', 'like', "%{$kw}%")
                      ->orWhere('reference_code', 'like', "%{$kw}%")
                      ->orWhereHas('documentarySeries', fn($s) => $s->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('documentarySubseries', fn($s) => $s->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('organizationalUnit', fn($u) => $u->where('name', 'like', "%{$kw}%"))
                      ->orWhereHas('organizationalUnit.entity', fn($e) => $e->where('name', 'like', "%{$kw}%"));
                }
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $records->map(fn(InventoryRecord $record) => [
            'codigo_referencia'         => $record->reference_code,
            'titulo'                    => $record->title,
            'descripcion'               => $record->description,
            'serie'                     => $record->documentarySeries?->name,
            'subserie'                  => $record->documentarySubseries?->name,
            'entidad'                   => $record->organizationalUnit?->entity?->name,
            'dependencia'               => $record->organizationalUnit?->name,
            'objeto_inventario'         => InventoryRecord::INVENTORY_PURPOSES[$record->inventory_purpose] ?? $record->inventory_purpose,
            'fechas'                    => $record->date_range,
            'ubicacion'                 => $record->location,
            'folios'                    => $record->folios,
            'soporte'                   => $record->storageMedium?->name,
            'tipo_unidad_almacenamiento' => InventoryRecord::STORAGE_UNIT_TYPES[$record->storage_unit_type] ?? $record->storage_unit_type,
            'cantidad_unidades'         => $record->storage_unit_quantity,
            'tiene_digitalizado'        => ! empty($record->attachments),
            'nivel_prioridad'           => $record->priorityLevel?->name,
            'notas'                     => $record->notes,
            'creado_por'                => $record->creator?->name,
            'fecha_creacion'            => $record->created_at?->format('d/m/Y H:i'),
            'actualizado_por'           => $record->updater?->name,
            'fecha_actualizacion'       => $record->updated_at?->format('d/m/Y H:i'),
        ])->toArray();
    }
}
