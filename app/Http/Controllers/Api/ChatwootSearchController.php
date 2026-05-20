<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatwootSearchController extends Controller
{
    /**
     * Endpoint para el bot de Chatwoot.
     * Busca en tiempo real en el Sistema Unificado de Registro (SUR)
     * y en el Inventario Documental (FUID).
     *
     * GET /api/chatwoot/search?q=texto&limit=5
     */
    public function search(Request $request): JsonResponse
    {
        // Validar token de seguridad
        $token = $request->header('X-Chatwoot-Token') ?? $request->query('token');
        if ($token !== config('app.chatwoot_api_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = trim($request->query('q', ''));
        $limit = min((int) $request->query('limit', 5), 10);

        if (strlen($query) < 2) {
            return response()->json([
                'query'   => $query,
                'results' => [],
                'summary' => 'Consulta muy corta para buscar.',
            ]);
        }

        $surResults       = $this->searchSUR($query, $limit);
        $inventoryResults = $this->searchInventory($query, $limit);

        $total = count($surResults) + count($inventoryResults);

        $summary = $total > 0
            ? "Se encontraron {$total} registro(s) relacionados con \"{$query}\"."
            : "No se encontraron registros relacionados con \"{$query}\".";

        return response()->json([
            'query'     => $query,
            'summary'   => $summary,
            'sur'       => $surResults,
            'inventory' => $inventoryResults,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // Sistema Unificado de Registro (Actos Administrativos)
    // ──────────────────────────────────────────────────────────────

    private function searchSUR(string $query, int $limit): array
    {
        $acts = AdministrativeAct::with([
            'organizationalUnit.entity',
            'documentarySeries',
            'documentarySubseries',
            'actClassification',
        ])
            ->where(function ($q) use ($query) {
                $q->where('filing_number', 'like', "%{$query}%")
                  ->orWhere('subject', 'like', "%{$query}%")
                  ->orWhereHas('documentarySeries', fn($s) => $s->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('documentarySubseries', fn($s) => $s->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('organizationalUnit', fn($u) => $u->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('organizationalUnit.entity', fn($e) => $e->where('name', 'like', "%{$query}%"));
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $acts->map(function (AdministrativeAct $act) {
            return [
                'consecutivo'  => $act->filing_number,
                'asunto'       => $act->subject,
                'vigencia'     => $act->vigencia,
                'serie'        => $act->documentarySeries?->name,
                'subserie'     => $act->documentarySubseries?->name,
                'entidad'      => $act->organizationalUnit?->entity?->name,
                'dependencia'  => $act->organizationalUnit?->name,
                'clasificacion'=> $act->actClassification?->name,
                'tiene_pdf'    => ! $act->lacksPdf(),
                'fecha_creacion' => $act->created_at?->format('d/m/Y'),
            ];
        })->toArray();
    }

    // ──────────────────────────────────────────────────────────────
    // Inventario Documental (FUID)
    // ──────────────────────────────────────────────────────────────

    private function searchInventory(string $query, int $limit): array
    {
        $records = InventoryRecord::with([
            'organizationalUnit.entity',
            'documentarySeries',
            'documentarySubseries',
            'storageMedium',
            'priorityLevel',
        ])
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('reference_code', 'like', "%{$query}%")
                  ->orWhereHas('documentarySeries', fn($s) => $s->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('documentarySubseries', fn($s) => $s->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('organizationalUnit', fn($u) => $u->where('name', 'like', "%{$query}%"))
                  ->orWhereHas('organizationalUnit.entity', fn($e) => $e->where('name', 'like', "%{$query}%"));
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $records->map(function (InventoryRecord $record) {
            return [
                'codigo_referencia' => $record->reference_code,
                'titulo'            => $record->title,
                'descripcion'       => $record->description,
                'serie'             => $record->documentarySeries?->name,
                'subserie'          => $record->documentarySubseries?->name,
                'entidad'           => $record->organizationalUnit?->entity?->name,
                'dependencia'       => $record->organizationalUnit?->name,
                'fechas'            => $record->date_range,
                'ubicacion'         => $record->location,
                'folios'            => $record->folios,
                'soporte'           => $record->storageMedium?->name,
            ];
        })->toArray();
    }
}
