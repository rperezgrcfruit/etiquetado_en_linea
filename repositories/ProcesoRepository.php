<?php
/**
 * ProcesoRepository.php
 * Encapsula queries de la tabla procesos y recepcion_granel.
 *
 * Reemplaza la logica suelta en:
 *   - api/get_proceso_normal_detalle.php
 *   - api/get_procesos_normales.php
 *   - api/get_procesos_primarios.php
 *   - api/get_procesos_secundarios.php
 */
require_once __DIR__ . '/../config/Repository.php';

class ProcesoRepository extends Repository
{
    protected string $table = 'procesos';

    /**
     * Get detalle del proceso (JOIN con recepcion_granel + productores).
     */
    public function getDetalleProceso(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT procesos.kilos as kiloEntrada, procesos.fecha as fechaProceso,
                    procesos.*, recepcion_granel.*, productores.*
             FROM procesos
             INNER JOIN recepcion_granel ON procesos.lote = recepcion_granel.lote
             INNER JOIN productores ON recepcion_granel.productor = productores.codigo
             WHERE procesos.temporada = :temporada
               AND procesos.estado = 'ABIERTO'
               AND procesos.empresa = :empresa
               AND tipo_proceso = 'NORMAL'
               AND n_proceso = :n_proceso
               AND recepcion_granel.temporada = :temporada_rg
               AND recepcion_granel.exportadora = :empresa_rg
               AND productores.empresa = :empresa_prod",
            [
                'temporada'    => $temporada,
                'empresa'      => $empresa,
                'n_proceso'    => $n_proceso,
                'temporada_rg' => $temporada,
                'empresa_rg'   => $empresa,
                'empresa_proc' => $empresa,
            ]
        );
    }

    /**
     * Get all open procesos of a given type.
     *
     * @param string $tipoProceso 'NORMAL' | 'CALIBRADO' | 'REPROCESO' | 'SECONDARY'
     */
    public function getOpenProcesos(string $tipoProceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        // Normalize tipo_proceso for the query
        $tipoMap = [
            'NORMAL'    => 'NORMAL',
            'CALIBRADO' => 'CALIBRADO',
            'REPROCESO' => 'REPROCESO',
            'REEMBALAJE' => 'REEMBALAJE',
        ];
        $tipoSql = $tipoMap[$tipoProceso] ?? $tipoProceso;

        return $this->fetchAll(
            "SELECT * FROM procesos
             WHERE temporada = ? AND estado = 'ABIERTO'
               AND empresa = ? AND tipo_proceso = ?
             GROUP BY n_proceso
             ORDER BY n_proceso DESC",
            [$temporada, $empresa, $tipoSql]
        );
    }

    /**
     * Detalle del proceso NORMAL (exacto al original — sin filtro estado/ABERTO
     * y sin JOIN a productores, para compatibilidad con vistas existentes).
     */
    public function getDetalleProcesoNormal(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT * FROM procesos INNER JOIN recepcion_granel ON procesos.lote = recepcion_granel.lote
             WHERE procesos.n_proceso = :n_proceso
               AND procesos.tipo_proceso = 'NORMAL'
               AND procesos.empresa = :empresa
               AND procesos.temporada = :temporada
               AND recepcion_granel.exportadora = :empresa2
               AND recepcion_granel.temporada = :temporada2",
            ['n_proceso' => $n_proceso, 'empresa' => $empresa, 'temporada' => $temporada,
             'empresa2' => $empresa, 'temporada2' => $temporada]
        );
    }

    /**
     * Get exportacion folios for a proceso (todos los tipos: COMPLETO/SALDO/MUESTRA).
     */
    public function getFoliosIngresadosExpo(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT existencia_exportacion.*,
                    ROUND((embalajes.`peso neto` * existencia_exportacion.cajas), 2) AS kilosExportacion,
                    ROUND((embalajes.deshidratacion * existencia_exportacion.cajas), 2) AS deshidratacion_total
             FROM existencia_exportacion
             INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
             WHERE n_proceso = :n_proceso
               AND tipo_proceso = 'NORMAL'
               AND existencia_exportacion.empresa = :empresa
               AND existencia_exportacion.temporada = :temporada
               AND embalajes.empresa = :empresa2
             ORDER BY folio DESC",
            ['n_proceso' => $n_proceso, 'empresa' => $empresa, 'temporada' => $temporada, 'empresa2' => $empresa]
        );
    }

    /**
     * Get detalle de existencia exportacion (folios ingresados) for un proceso.
     */
    public function getFoliosIngresados(string $n_proceso, string $tipoPallet = 'COMPLETO'): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT existencia_exportacion.*, embalajes.*,
                    ROUND((embalajes.`peso neto` * existencia_exportacion.cajas), 2) AS kilosExportacion,
                    ROUND((embalajes.deshidratacion * existencia_exportacion.cajas), 2) AS deshidratacion_total
             FROM existencia_exportacion
             INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
             WHERE existencia_exportacion.empresa = :empresa
               AND existencia_exportacion.temporada = :temporada
               AND existencia_exportacion.tipo_pallet = :tipo_pallet
               AND n_proceso = :n_proceso
               AND tipo_proceso = 'NORMAL'
               AND embalajes.empresa = :empresa2
             ORDER BY folio DESC",
            [
                'empresa'      => $empresa,
                'temporada'    => $temporada,
                'tipo_pallet'  => $tipoPallet,
                'n_proceso'    => $n_proceso,
                'empresa2'     => $empresa,
            ]
        );
    }

    /**
     * Get folios ingresados comerciales para un proceso.
     */
    public function getFoliosIngresadosCom(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT * FROM existencia_comercial
             WHERE empresa = :empresa AND temporada = :temporada
               AND n_proceso = :n_proceso AND tipo_proceso = 'NORMAL'
             ORDER BY folio DESC",
            ['empresa' => $empresa, 'temporada' => $temporada, 'n_proceso' => $n_proceso]
        );
    }

    /**
     * Get open procesos de reembalaje (tipo_proceso = 'REEMBALAJE').
     */
    public function getOpenReembalajeProcesos(): array
    {
        return $this->getOpenProcesos('REEMBALAJE');
    }

    /**
     * Get detalle del proceso REEMBALAJE — header data from procesos table.
     *
     * In the VB.NET reembalaje flow, the procesos table stores especie, variedad,
     * productor (code), sdp, csg directly (copied from the input export folio at
     * process creation). This query retrieves those header fields plus r_social
     * and csg from productores for display.
     */
    public function getDetalleProcesoReembalaje(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT procesos.*,
             productores.r_social AS productor_nombre,
             productores.csg AS csg_productor
             FROM procesos
             LEFT JOIN productores
               ON procesos.productor = productores.codigo
              AND procesos.empresa = productores.empresa
             WHERE procesos.n_proceso = :n_proceso
               AND procesos.tipo_proceso = 'REEMBALAJE'
               AND procesos.empresa = :empresa
               AND procesos.temporada = :temporada",
            [
                'n_proceso'   => $n_proceso,
                'empresa'     => $empresa,
                'temporada'   => $temporada,
            ]
        );
    }

    /**
     * Get folios de ENTRADA (input pallets being repackaged) for a REEMBALAJE process.
     *
     * In the VB.NET reembalaje flow, cargar_entrada() queries existencia_exportacion
     * WHERE reembalaje = n_proceso. These are the export folios linked to this
     * reembalaje process via the `reembalaje` column.
     */
    public function getFoliosEntradaReembalaje(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT existencia_exportacion.*,
                    ROUND((embalajes.`peso neto` * existencia_exportacion.cajasExis), 2) AS kilosExportacion,
                    productores.r_social AS productor_nombre,
                    productores.csg AS csg_productor
             FROM existencia_exportacion
             INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
             INNER JOIN productores ON existencia_exportacion.productor = productores.codigo
             WHERE existencia_exportacion.reembalaje = :n_proceso
               AND existencia_exportacion.tipo_proceso = 'REEMBALAJE'
               AND existencia_exportacion.empresa = :empresa
               AND existencia_exportacion.temporada = :temporada
               AND embalajes.empresa = :empresa2
               AND productores.empresa = :empresa3
             ORDER BY folio DESC",
            [
                'n_proceso'  => $n_proceso,
                'empresa'    => $empresa,
                'temporada'  => $temporada,
                'empresa2'   => $empresa,
                'empresa3'   => $empresa,
            ]
        );
    }

    /**
     * Get exportacion folios for a REEMBALAJE proceso.
     */
    public function getFoliosIngresadosExpoReembalaje(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT existencia_exportacion.*,
                    ROUND((embalajes.`peso neto` * existencia_exportacion.cajas), 2) AS kilosExportacion,
                    ROUND((embalajes.deshidratacion * existencia_exportacion.cajas), 2) AS deshidratacion_total
             FROM existencia_exportacion
             INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
             WHERE n_proceso = :n_proceso
               AND tipo_proceso = 'REEMBALAJE'
               AND existencia_exportacion.empresa = :empresa
               AND existencia_exportacion.temporada = :temporada
               AND embalajes.empresa = :empresa2
             ORDER BY folio DESC",
            ['n_proceso' => $n_proceso, 'empresa' => $empresa, 'temporada' => $temporada, 'empresa2' => $empresa]
        );
    }

    /**
     * Get folios ingresados comerciales para un proceso REEMBALAJE.
     */
    public function getFoliosIngresadosComReembalaje(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT * FROM existencia_comercial
             WHERE empresa = :empresa AND temporada = :temporada
               AND n_proceso = :n_proceso AND tipo_proceso = 'REEMBALAJE'
             ORDER BY folio DESC",
            ['empresa' => $empresa, 'temporada' => $temporada, 'n_proceso' => $n_proceso]
        );
    }
}
