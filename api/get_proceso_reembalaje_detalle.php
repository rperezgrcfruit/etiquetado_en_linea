<?php
//session_start();
include_once $_SERVER['DOCUMENT_ROOT'] . '/packing/config/db.php';
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/**
 * Obtiene los datos de cabecera del proceso REEMBALAJE.
 *
 * En el VB.NET, al crear un proceso REEMBALAJE (busqueda_folio), se INSERTA en
 * la tabla `procesos` con las columnas: especie, variedad, productor (codigo),
 * sdp, csg, lote (=folio exportacion de entrada), cajas, kilos, etc.
 * Estos datos vienen directamente de `procesos`, NO de `existencia_exportacion`.
 *
 * La tabla `procesos` para REEMBALAJE almacena todos estos campos porque
 * el proceso se crea copiando los datos del folio de exportación de entrada
 * que se busca en busqueda_folio().
 *
 * Esta función devuelve los datos de cabecera para:
 *   - Campos hidden del formulario (productor, especie, variedad, sdp, csg)
 *   - Cargar los selects (embalajes, calibres, etiquetas, categorias, plu, variedades)
 *   - Mostrar información del proceso en el encabezado
 *
 * @param string $n_proceso  Número de proceso REEMBALAJE
 * @return array  Array con los datos de cabecera del proceso
 */
function obtenerDetalleProcesoReembalaje($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;

    // Para REEMBALAJE, la tabla procesos NO tiene productor/sdp/csg.
    // Obtenemos los datos de cabecera desde el PRIMER folio de entrada (existencia_exportacion
    // donde reembalaje = n_proceso), que fue el folio origen del reembalaje.
    $query = "SELECT 
                ee.especie,
                ee.variedad,
                ee.productor,
                ee.sdp,
                ee.csg,
                pr.lote as folio_origen,
                p.r_social AS productor_nombre,
                p.csg AS csg_productor,
                pr.fecha,
                pr.lote,
                pr.espe,
                pr.varie,
                pr.bins,
                pr.kilos,
                pr.empresa
              FROM procesos pr
              INNER JOIN existencia_exportacion ee ON ee.reembalaje = pr.n_proceso
                  AND ee.empresa = pr.empresa
                  AND ee.temporada = pr.temporada
              LEFT JOIN productores p ON ee.productor = p.codigo
                  AND p.empresa = ee.empresa
              WHERE pr.n_proceso = :n_proceso
                AND pr.tipo_proceso = 'REEMBALAJE'
                AND pr.empresa = :empresa
                AND pr.temporada = :temporada
              LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso'    => $n_proceso,
        'empresa'      => $_SESSION['empresa'],
        'temporada'    => $temporadaActual,
    ]);
    $procesosReembalaje = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $procesosReembalaje;
}

/**
 * Obtiene los folios de exportación DE ENTRADA (input pallets being repackaged).
 *
 * En el VB.NET, cargar_entrada() consulta existencia_exportacion WHERE
 * reembalaje = n_proceso. Estos son los folios que fueron marcados con
 * estado='REEMBALAJE' y reembalaje=n_proceso al buscarlos en busqueda_folio().
 *
 * @param string $n_proceso  Número de proceso REEMBALAJE
 * @return array  Folios de entrada con datos de producto, embalaje, peso
 */
function obtenerDetalleProcesoReembalajeEntrada($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;

    $query = "SELECT existencia_exportacion.*,
              ROUND((embalajes.`peso neto` * existencia_exportacion.cajasExis), 2) AS kilosExportacion,
              productores.r_social AS productor_nombre,
              productores.csg AS csg_productor
              FROM existencia_exportacion
              INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
              INNER JOIN productores ON existencia_exportacion.productor = productores.codigo
              WHERE existencia_exportacion.reembalaje = :n_proceso
              AND existencia_exportacion.empresa = :empresa
              AND existencia_exportacion.temporada = :temporada
              AND embalajes.empresa = :empresa2
              AND productores.empresa = :empresa3
              ORDER BY folio DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa'   => $_SESSION['empresa'],
        'temporada' => $temporadaActual,
        'empresa2'  => $_SESSION['empresa'],
        'empresa3'  => $_SESSION['empresa'],
    ]);
    $procesosReembalajeEntrada = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $procesosReembalajeEntrada;
}

/**
 * Obtiene los folios de exportación DE SALIDA (output pallets created by reembalaje).
 *
 * En el VB.NET, cargar_existencia_expo() consulta existencia_exportacion WHERE
 * n_proceso = n_proceso AND tipo_proceso = 'REEMBALAJE'. Estos son los nuevos
 * folios creados al ingresar cajas en el proceso de reembalaje.
 *
 * @param string $n_proceso  Número de proceso REEMBALAJE
 * @return array  Output folios with embalaje details and computed weights
 */
function obtenerDetalleProcesoReembalajeExpo($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;

    $query = "SELECT existencia_exportacion.*,
    ROUND((embalajes.`peso neto` * existencia_exportacion.cajas), 2) AS kilosExportacion
    , ROUND((embalajes.deshidratacion * existencia_exportacion.cajas), 2) AS deshidratacion_total
    FROM existencia_exportacion
    INNER JOIN embalajes ON existencia_exportacion.embalaje = embalajes.codigo
    WHERE n_proceso = :n_proceso AND tipo_proceso = 'REEMBALAJE'
    AND existencia_exportacion.empresa = :empresa
    AND existencia_exportacion.temporada = :temporada
    AND embalajes.empresa = :empresa2
    ORDER BY folio DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa'   => $_SESSION['empresa'],
        'temporada' => $temporadaActual,
        'empresa2'  => $_SESSION['empresa']
    ]);
    $procesosReembalajeExpo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $procesosReembalajeExpo;
}

/**
 * Obtiene los folios comerciales de un proceso REEMBALAJE.
 *
 * En el VB.NET, cargar_existencia_comercial() consulta
 * existencia_comercial WHERE n_proceso = n_proceso AND tipo_proceso = 'REEMBALAJE'
 *
 * @param string $n_proceso  Número de proceso REEMBALAJE
 * @return array  Folios comerciales
 */
function obtenerDetalleProcesoReembalajeCom($n_proceso)
{
    global $pdo;
    $temporadaActual = TEMPORADA_ACTUAL;

    $query = "SELECT * FROM existencia_comercial
    WHERE n_proceso = :n_proceso AND tipo_proceso = 'REEMBALAJE'
    AND empresa = :empresa AND temporada = :temporada
    ORDER BY folio DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'n_proceso' => $n_proceso,
        'empresa'   => $_SESSION['empresa'],
        'temporada' => $temporadaActual
    ]);
    $procesosReembalajeCom = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_kilos_com = array_sum(array_column($procesosReembalajeCom, 'kilosComercial'));
    return $procesosReembalajeCom;
}
?>
