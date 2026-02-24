<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza los códigos de unidades organizacionales y series/subseries CCD
 * para que coincidan con el Cuadro de Clasificación Documental oficial.
 *
 * Migración IDEMPOTENTE: puede ejecutarse múltiples veces sin efectos secundarios.
 *
 * Cambios:
 *  - Unidades organizacionales: códigos numéricos → siglas (100→DA, 110→SGA, etc.)
 *  - Serie CCD: 02 (Actos administrativos) → 03
 *  - Serie CCD: 09 (Comunicaciones oficiales) → 24
 *  - Subseries de Actos administrativos: códigos 01→03, 02→04, agrega 02 Circulares
 *  - Subseries de Comunicaciones oficiales: renombra Oficios y Internas
 *  - Elimina (soft delete) las series que ocupaban los códigos 03 y 24 sin registros
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->updateOrganizationalUnitCodes();
            $this->softDeleteConflictingSeries();
            $this->updateSeriesCodes();
            $this->updateSubseriesActosAdministrativos();
            $this->updateSubseriesComunicacionesOficiales();
        });
    }

    public function down(): void
    {
        // Reversión omitida: cambio de datos de negocio, revertir manualmente si es necesario.
    }

    // ─── 1. Unidades organizacionales ────────────────────────────────────────

    private function updateOrganizationalUnitCodes(): void
    {
        $map = [
            '100' => 'DA',
            '110' => 'SGA',
            '120' => 'SGCC',
            '130' => 'SH',
            '140' => 'SP',
            '150' => 'SOP',
            '160' => 'SDSC',
            '170' => 'CI',
            '180' => 'UMATA',
            '190' => 'ITT',
        ];

        foreach ($map as $from => $to) {
            // Las claves numéricas se convierten a int en PHP; forzar string
            // para que MySQL genere WHERE code = '100' (con comillas) y no un DOUBLE cast
            $fromStr = (string) $from;

            $sourceExists = DB::table('organizational_units')
                ->where('code', $fromStr)
                ->whereNull('deleted_at')
                ->exists();

            $targetExists = DB::table('organizational_units')
                ->where('code', $to)
                ->whereNull('deleted_at')
                ->exists();

            // Solo actualiza si el origen existe y el destino no existe aún
            if ($sourceExists && ! $targetExists) {
                DB::table('organizational_units')
                    ->where('code', $fromStr)
                    ->whereNull('deleted_at')
                    ->update(['code' => $to, 'updated_at' => now()]);
            }
        }
    }

    // ─── 2. Eliminar (soft delete) series que ocupan 03 y 24 sin registros ──

    private function softDeleteConflictingSeries(): void
    {
        // Nombres exactos de las series que DEBEN tener esos códigos (no eliminar)
        $protectedNames = [
            '03' => ['Actos administrativos', 'Actos Administrativos'],
            '24' => ['Comunicaciones oficiales', 'Comunicaciones Oficiales'],
        ];

        foreach ($protectedNames as $code => $keepNames) {
            DB::table('documentary_series')
                ->where('code', $code)
                ->where('context', 'ccd')
                ->whereNull('deleted_at')
                ->whereNotIn('name', $keepNames)
                // Solo si no tiene registros asociados
                ->whereNotExists(fn ($q) => $q->from('inventory_records')
                    ->whereColumn('documentary_series_id', 'documentary_series.id'))
                ->whereNotExists(fn ($q) => $q->from('administrative_acts')
                    ->whereColumn('documentary_series_id', 'documentary_series.id'))
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }
    }

    // ─── 3. Actualizar códigos de series ────────────────────────────────────

    private function updateSeriesCodes(): void
    {
        $seriesMap = [
            // [código_origen, nombre_parcial, código_destino]
            ['02', 'Actos', '03'],
            ['09', 'Comunicaciones', '24'],
        ];

        foreach ($seriesMap as [$from, $nameContains, $to]) {
            // Ya está en el código destino → nada que hacer
            $alreadyDone = DB::table('documentary_series')
                ->where('code', $to)
                ->where('context', 'ccd')
                ->whereNull('deleted_at')
                ->where('name', 'like', "%{$nameContains}%")
                ->exists();

            if ($alreadyDone) {
                continue;
            }

            DB::table('documentary_series')
                ->where('code', $from)
                ->where('context', 'ccd')
                ->whereNull('deleted_at')
                ->where('name', 'like', "%{$nameContains}%")
                ->update(['code' => $to, 'updated_at' => now()]);
        }
    }

    // ─── 4. Subseries de Actos administrativos (código 03) ──────────────────

    private function updateSubseriesActosAdministrativos(): void
    {
        $series = DB::table('documentary_series')
            ->where('code', '03')
            ->where('context', 'ccd')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%Actos%')
            ->first(['id']);

        if (! $series) {
            return;
        }

        $sid = $series->id;

        // Decretos: 01 → 03 (solo si aún está en 01)
        DB::table('documentary_subseries')
            ->where('documentary_series_id', $sid)
            ->where('code', '01')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%Decreto%')
            ->update(['code' => '03', 'updated_at' => now()]);

        // Resoluciones: 02 → 04 (solo si aún está en 02)
        DB::table('documentary_subseries')
            ->where('documentary_series_id', $sid)
            ->where('code', '02')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%Resoluci%')
            ->update(['code' => '04', 'updated_at' => now()]);

        // Circulares (código 02): insertar solo si no existe
        $circularesExiste = DB::table('documentary_subseries')
            ->where('documentary_series_id', $sid)
            ->where('code', '02')
            ->whereNull('deleted_at')
            ->exists();

        if (! $circularesExiste) {
            DB::table('documentary_subseries')->insert([
                'code'                   => '02',
                'name'                   => 'Circulares',
                'description'            => null,
                'documentary_series_id'  => $sid,
                'context'                => 'ccd',
                'is_active'              => true,
                'retention_years'        => null,
                'final_disposition'      => null,
                'created_at'             => now(),
                'updated_at'             => now(),
                'deleted_at'             => null,
            ]);
        }
    }

    // ─── 5. Subseries de Comunicaciones oficiales (código 24) ───────────────

    private function updateSubseriesComunicacionesOficiales(): void
    {
        $series = DB::table('documentary_series')
            ->where('code', '24')
            ->where('context', 'ccd')
            ->whereNull('deleted_at')
            ->where('name', 'like', '%Comunicaciones%')
            ->first(['id']);

        if (! $series) {
            return;
        }

        $sid = $series->id;

        // Oficios → Comunicaciones Externas (código 01 sin tocar el código)
        DB::table('documentary_subseries')
            ->where('documentary_series_id', $sid)
            ->where('code', '01')
            ->whereNull('deleted_at')
            ->whereIn('name', ['Oficios', 'Comunicaciones Externas'])
            ->update(['name' => 'Comunicaciones Externas', 'updated_at' => now()]);

        // Internas → Comunicaciones Internas (código 02 sin tocar el código)
        DB::table('documentary_subseries')
            ->where('documentary_series_id', $sid)
            ->where('code', '02')
            ->whereNull('deleted_at')
            ->whereIn('name', ['Internas', 'Comunicaciones Internas'])
            ->update(['name' => 'Comunicaciones Internas', 'updated_at' => now()]);
    }
};
