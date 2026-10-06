<?php
/**
 * FolioRepository.php
 * Encapsula queries de folios: existencia_exportacion, existencia_comercial,
 * existencia_calibrado, historico, asignacion_folios.
 *
 * Reemplaza la logica suelta en ModelIngresoFolio (que era codigo muerto) y
 * en las funciones del backend. Este es el repositorio central de negocio.
 */
require_once __DIR__ . '/../config/Repository.php';

class FolioRepository extends Repository
{
    protected string $table = 'existencia_exportacion';

    /**
         * Get the next available folio (transaction-safe, uses FOR UPDATE).
         * Maps tipo_pallet (COMPLETO/SALDO/MUESTRA/DIFERENCIA) to the correct
         * tipo_folio in asignacion_folios for exportacion folios.
         */
        public function getFolioDisponible(string $tipoPallet): ?string
        {
            $empresa = $_SESSION['empresa'];
            $temporada = TEMPORADA_ACTUAL;

            // tipo_folio in asignacion_folios matches the tipo_pallet value directly
            $tipoFolio = $tipoPallet;

            return $this->getNextFolio($tipoFolio, $empresa, $temporada);
        }

    /**
     * Insert a new exportacion folio and record historial.
     * Uses transaction to ensure atomicity.
     *
     * @param array $data POST data from the form
     * @param array $procesos Detalle proceso (from getDetalleProceso)
     * @return int|false Inserted row ID or false on failure
     */
    public function insertExportacion(array $data, array $procesos): int|false
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;
        $usuario = $_SESSION['username'];

        $numeroProceso = $procesos[0]['n_proceso'];
        $especie = $procesos[0]['especie'];
        $variedad = $procesos[0]['variedad'];
        $sdp = $procesos[0]['sdp'];
        $productor = $procesos[0]['productor'];
        $csg = $procesos[0]['csg'] ?? '';
        $tipoProceso = $data['tipo_proceso'] ?? 'NORMAL';

        // Get empaletaje weight info
        $embalaje = $data['cbEmbalaje'];
        $embalajeInfo = $this->fetch(
            "SELECT deshidratacion FROM embalajes WHERE empresa = ? AND especie = ? AND codigo = ?",
            [$empresa, $especie, $embalaje]
        );
        $deshidratacionCaja = $embalajeInfo['deshidratacion'] ?? 0;
        $totalDeshi = intval($data['txtCajas']) * floatval($deshidratacionCaja);

        // Get next available folio
        $folio = $this->getFolioDisponible($data['cbTipoPallet']);
        if (!$folio) {
            return false;
        }

        $fechaEmbalaje = date("Y-m-d");

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO existencia_exportacion (
                n_proceso, tipo_proceso, `fecha embalaje`, empresa, productor, productor_etiq,
                sdp, predio, csg, especie, variedad, variedad_etiq,
                folio_origen, folio, embalaje, etiqueta, categoria, plu, calibre,
                cajas, cajasExis, tipo_pallet, base_pallet, deshidratacion_caja,
                deshidratacion_total, packing, `fecha recepcion`, estado, temporada
            ) VALUES (
                :n_proceso, :tipo_proceso, :fecha_embalaje, :empresa, :productor,
                :productor_etiq, :sdp, :predio, :csg, :especie, :variedad, :variedad_etiq,
                :folio_origen, :folio, :embalaje, :etiqueta, :categoria, :plu, :calibre,
                :cajas, :cajas_exis, :tipo_pallet, :base_pallet, :deshidratacion_caja,
                :deshidratacion_total, :packing, :fecha_recepcion, :estado, :temporada
            )";

             if ($data['cbTipoPallet'] === 'DIFERENCIA') {
                    $data['cbTipoPallet'] = 'SALDO';
                }

            $this->execute($sql, [
                'n_proceso'           => $numeroProceso,
                'tipo_proceso'          => $tipoProceso,
                'empresa'               => $empresa,
                'productor'             => $productor,
                'productor_etiq'        => $data['cbProductorEtiquetado'] ?? $productor,
                'sdp'                   => $sdp,
                'predio'                => $sdp,
                'csg'                   => $csg,
                'especie'               => $especie,
                'variedad'              => $variedad,
                'variedad_etiq'         => $variedad,
                'folio_origen'          => '',
                'folio'                 => $folio,
                'embalaje'              => $embalaje,
                'etiqueta'              => $data['cbEtiqueta'],
                'categoria'             => $data['cbCategoria'],
                'plu'                   => $data['cbPlu'],
                'calibre'               => $data['cbCalibre'],
                'cajas'                 => $data['txtCajas'],
                'cajas_exis'            => $data['txtCajas'],
                'tipo_pallet'           => $data['cbTipoPallet'],
                'base_pallet'           => $data['cbBasePallet'] ?? '',
                'deshidratacion_caja'   => $deshidratacionCaja,
                'deshidratacion_total'  => $totalDeshi,
                'packing'               => PLANTA_CENTRAL_CSP,
                'fecha_embalaje'        => $fechaEmbalaje,
                'fecha_recepcion'       => $fechaEmbalaje,
                'estado'                => 'EXISTENCIA',
                'temporada'             => $temporada,
            ]);

            // Record historial
            $this->execute(
                "INSERT INTO historico
                 (folio_lote, tipo, cantidad, n_operacion, tipo_operacion,
                  fecha_hora, tipo_movimiento, usuario, empresa, temporada)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
                [
                    $folio, 'PALLET/SALDO', $data['txtCajas'], $numeroProceso,
                    'PROCESO NORMAL', 'INGRESO', $usuario, $empresa, $temporada
                ]
            );

            // Mark folio as used
            $this->useFolio($folio, $empresa, $temporada);

            $this->db->commit();
            return $this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Folio insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert a new comercial folio.
     */

    
    public function insertComercial(array $data, array $procesos): int|false
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;
        $usuario = $_SESSION['username'];
        $numeroProceso = $procesos[0]['n_proceso'];
        $especie = $procesos[0]['especie'];
        $variedad = $procesos[0]['variedad'];
        $productor = $procesos[0]['productor'];

        $folio = $this->getNextFolio('COMERCIAL', $empresa, $temporada);
        //echo "Next available folio for COMERCIAL: " . ($folio ?? 'none') . "\n";
        if (!$folio) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->query(
                "INSERT INTO existencia_comercial
                 (n_proceso, tipo_proceso, fecha, planta, empresa, productor, especie,
                  variedad, folio, envase, unidad, categoria, calibre, kilos, estado, temporada)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $numeroProceso, 'NORMAL', date('Y-m-d'),
                    'GRCFRUIT', $empresa, $productor, $especie, $variedad, $folio,
                    $data['cbEnvaseComercial'] ?? 'BIN', 'KG', $data['cbCategoriaComercial'],
                    $data['cbCalibreComercial'], $data['inputTextKgNetoCom'], 'EXISTENCIA', $temporada
                ]
            );

            $this->execute(
                "INSERT INTO historico
                 (folio_lote, tipo, cantidad, n_operacion, tipo_operacion,
                  fecha_hora, tipo_movimiento, usuario, empresa, temporada)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
                [
                    $folio, 'COMERCIAL', 0, $numeroProceso,
                    'PROCESO NORMAL', 'INGRESO', $usuario, $empresa, $temporada
                ]
            );

            $this->useFolio($folio, $empresa, $temporada);

            $this->db->commit();
            return $this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Comercial folio insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark folio as printed.
     */
    public function markPrinted(string $folio, string $tipoTabla = 'exportacion'): int
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        $table = $tipoTabla === 'comercial'
            ? 'existencia_comercial'
            : 'existencia_exportacion';

        return $this->execute(
            "UPDATE {$table} SET impreso = 1
             WHERE folio = :folio AND empresa = :empresa AND temporada = :temporada",
            ['folio' => $folio, 'empresa' => $empresa, 'temporada' => $temporada]
        );
    }

    /**
     * Get folios ingresados with saldos (SALDO + MUESTRA tipos).
     */
    public function getFoliosIngresadosConSaldo(string $n_proceso): array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        return $this->fetchAll(
            "SELECT existencia_exportacion.*, embalajes.*
             FROM existencia_exportacion
             INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
             WHERE existencia_exportacion.empresa = :empresa
               AND existencia_exportacion.temporada = :temporada
               AND (existencia_exportacion.tipo_pallet = 'SALDO' OR existencia_exportacion.tipo_pallet = 'MUESTRA')
               AND n_proceso = :n_proceso
               AND tipo_proceso = 'NORMAL'
               AND embalajes.empresa = :empresa2",
            ['empresa' => $empresa, 'temporada' => $temporada, 'n_proceso' => $n_proceso, 'empresa2' => $empresa]
        );
    }

    /**
     * Insert a new CALIBRADO (Primario) folio into existencia_calibrado.
     * Uses transaction to ensure atomicity.
     *
     * @param array $data POST data from the form
     * @param array $procesos Detalle proceso (from getDetalleProceso)
     * @return int|false Inserted row ID or false on failure
     */
    public function insertCalibrado(array $data, array $procesos): int|false
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;
        $usuario = $_SESSION['username'];

        $numeroProceso = $procesos[0]['n_proceso'];
        $especie = $procesos[0]['especie'];
        $variedad = $procesos[0]['variedad'];
        $productor = $procesos[0]['productor'];
        $sdp = $procesos[0]['sdp'] ?? '';

        $fechaActual = date('Y-m-d');

        // Get next available folio for CALIBRADO/PRIMARIO
        $folio = $this->getNextFolio('CALIBRADO/PRIMARIO', $empresa, $temporada);
        if (!$folio) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $sql = "INSERT INTO existencia_calibrado (
                n_proceso, tipo_proceso, fecha, empresa, productor, especie, variedad,
                folio, embalaje, categoria, calibre, unidad, kg_bruto, tara, cantidad, kilos,
                packing, estado, reproceso, temporada, n_despacho, desverdizado,
                ingreso_desverdizado, salida_desverdizado, sdp
            ) VALUES (
                :n_proceso, :tipo_proceso, :fecha, :empresa, :productor, :especie, :variedad,
                :folio, :embalaje, :categoria, :calibre, :unidad, :kg_bruto, :tara, :cantidad, :kilos,
                :packing, :estado, :reproceso, :temporada, :n_despacho, :desverdizado,
                :ingreso_desverdizado, :salida_desverdizado, :sdp
            )";

            $this->execute($sql, [
                'n_proceso'         => $numeroProceso,
                'tipo_proceso'      => 'CALIBRADO',
                'fecha'             => $fechaActual,
                'empresa'           => $empresa,
                'productor'         => $data['productor'] ?? $productor,
                'especie'           => $especie,
                'variedad'          => $variedad,
                'folio'             => $folio,
                'embalaje'          => 'BIN',
                'categoria'         => 'CAT1',
                'calibre'           => $data['cbColor'] ?? $data['cbCalibre'] ?? '',
                'unidad'            => 'UN',
                'kg_bruto'          => $data['txtKgBruto'] ?? 0,
                'tara'              => BIN_TARA,
                'cantidad'          => 1,
                'kilos'             => $data['txtKgNeto'] ?? $data['txtKgNeto'] ?? 0,
                'packing'           => PLANTA_CENTRAL_CSP,
                'estado'            => 'EXISTENCIA',
                'reproceso'         => '',
                'temporada'         => $temporada,
                'n_despacho'        => '',
                'desverdizado'      => '',
                'ingreso_desverdizado' => '',
                'salida_desverdizado' => '',
                'sdp'               => $sdp,
            ]);

            // Mark folio as used
            $this->useFolio($folio, $empresa, $temporada);

            // Record historial
            $this->execute(
                "INSERT INTO historico
                 (folio_lote, tipo, cantidad, n_operacion, tipo_operacion,
                  fecha_hora, tipo_movimiento, usuario, empresa, temporada)
                 VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)",
                [
                    $folio, 'CALIBRADO/PRIMARIO', $data['txtKgNeto'] ?? 0, $numeroProceso,
                    'CALIBRADO', 'INGRESO', $usuario, $empresa, $temporada
                ]
            );

            $this->db->commit();
            return $this->db->query("SELECT LAST_INSERT_ID()")->fetchColumn();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("Calibrado folio insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get next available folio for CALIBRADO/PRIMARIO tipo.
     */
    public function getFolioCalibradoDisponible(): ?array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        $sql = "SELECT folio FROM asignacion_folios
                WHERE estado_folio = 'DISPONIBLE'
                  AND tipo_folio = 'CALIBRADO/PRIMARIO'
                  AND empresa = ?
                LIMIT 1";

        $stmt = $this->db->query($sql, [$empresa]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get next available folio for COMERCIAL tipo.
     */
    public function getFolioComercialDisponible(): ?array
    {
        $empresa = $_SESSION['empresa'];
        $temporada = TEMPORADA_ACTUAL;

        $sql = "SELECT folio FROM asignacion_folios
                WHERE estado_folio = 'DISPONIBLE'
                  AND tipo_folio = 'COMERCIAL'
                  AND empresa = ?
                LIMIT 1";

        $stmt = $this->db->query($sql, [$empresa]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}