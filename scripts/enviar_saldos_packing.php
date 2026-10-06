<?php
/**
 * Script: enviar_saldos_packing.php
 * Descripción: Genera y envía 4 correos diarios informando sobre los saldos
 *              de packing según la prioridad, basado en la vista
 *              vista_saldos_packing.
 *
 * Cada correo se envía al inicio de cada turno de trabajo:
 *   1. Críticos por tiempo     (08:00)
 *   2. Reparación inmediata    (12:00)
 *   3. Completar en línea      (16:00)
 *   4. En curso                (20:00)
 *
 * Uso: php enviar_saldos_packing.php [prioridad]
 *   Sin argumentos: envía todos los correos
 *   Con argumento: envía solo el correo de la prioridad especificada
 *
 * Cron (ejemplo):
 *   0 8,12,16,20 * * 1-5 /usr/bin/php /var/www/grc/packing/scripts/enviar_saldos_packing.php
 */

require_once __DIR__ . '/../config/db.php';  // Carga $pdo y configuración

// Configuración de correo
$fromEmail = "noreply@grcfruit.cl";
$fromName = "Sistema de Saldos GRC Packing";
$fechaHoy = date('d/m/Y H:i');

// --- Cargar destinatarios ---
$correosFile = '/home/grc/.hermes/docs/correos_grc.md';
$destinatarios = [];

if (file_exists($correosFile)) {
    $lineas = file($correosFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        // Cada línea tiene formato: número|correo@dominio
        $partes = explode('|', $linea);
        if (isset($partes[1])) {
            $email = trim($partes[1]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = $email;
            }
        }
    }
}

// Si no se cargan correos del archivo, usar lista predefinida
if (empty($destinatarios)) {
    $destinatarios = [
        "rperez@grcfruit.cl",
        "cfigueroa@grcfruit.cl",
        "yrios@grcfruit.cl",
        "mdiaz@grcfruit.cl"
    ];
}

// --- Mapeo de prioridades ---
$prioridades = [
    "? CRITICO POR TIEMPO" => [
        "asunto" => "URGENTE: Saldos Críticos por Tiempo - {$fechaHoy}",
        "titulo" => "Saldos CRÍTICOS por Tiempo",
        "descripcion" => "Estos lotes requieren atención inmediata debido a la antigüedad del embalaje. Los lotes con mayor cantidad de días sin terminar son los de mayor prioridad.",
        "color" => "#cc0000",
        "color_fondo" => "#ffebee"
    ],
    "? REPA INMEDIATO (JUNTAR)" => [
        "asunto" => "ALERTA: Reparación Inmediata - Saldos Packing - {$fechaHoy}",
        "titulo" => "Reparación Inmediata (Juntar)",
        "descripcion" => "Estos lotes necesitan reparación inmediata. Se recomienda juntar cajas de los mismos lotes y variedades para optimizar el proceso.",
        "color" => "#ff9900",
        "color_fondo" => "#fff3e0"
    ],
    "? COMPLETAR EN LINEA" => [
        "asunto" => "Seguimiento: Completar en Línea - Saldos Packing - {$fechaHoy}",
        "titulo" => "Completar en Línea",
        "descripcion" => "Estos lotes pueden completarse en línea. Revise la producción disponible y priorice estos lotes durante el turno.",
        "color" => "#0066cc",
        "color_fondo" => "#e3f2fd"
    ],
    "? EN CURSO" => [
        "asunto" => "Informe: Lotes en Curso - Saldos Packing - {$fechaHoy}",
        "titulo" => "Lotes en Curso",
        "descripcion" => "Estos lotes están activos y en proceso de terminación. Continúe con el trabajo programado.",
        "color" => "#2c5aa0",
        "color_fondo" => "#e8eaf6"
    ]
];

// --- Procesar argumento opcional ---
$prioridadEspecifica = $argv[1] ?? null;
if ($prioridadEspecifica !== null) {
    // Permitir pasar solo parte del nombre
    $prioridadEspecifica = "? " . trim($prioridadEspecifica);
    $prioridades = array_filter($prioridades, function($key) use ($prioridadEspecifica) {
        return stripos($key, $prioridadEspecifica) !== false || stripos($key, trim($prioridadEspecifica, '? ')) !== false;
    }, ARRAY_FILTER_USE_KEY);

    if (empty($prioridades)) {
        error_log("[WARN] No se encontró la prioridad especificada: {$prioridadEspecifica}");
        exit(0);
    }
}

// --- Enviar correos ---
$correosEnviados = 0;
$totalCorreos = 0;

foreach ($prioridades as $prioridad => $info) {
    try {
        // Obtener saldos para esta prioridad
        $stmt = $pdo->prepare("
            SELECT
                Empresa,
                Temporada,
                Productor_Real,
                Especie,
                Variedad,
                Folio,
                Embalaje,
                Categoria,
                Calibre,
                Cajas_Actuales,
                Cajas_Faltantes,
                Porcentaje_Avance,
                Accion_Sugerida,
                Dias_Embalaje,
                Ubicacion_Estiba
            FROM vista_saldos_packing
            WHERE CONVERT(Prioridad USING utf8mb4) LIKE :prioridad
            ORDER BY Dias_Embalaje DESC, Cajas_Faltantes DESC
        ");
        $stmt->execute([':prioridad' => '%' . str_replace('? ', '', $prioridad) . '%']);
        $saldos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay saldos, saltar este correo
        if (count($saldos) === 0) {
            error_log("[INFO] No hay saldos para prioridad: {$prioridad}");
            continue;
        }

        // Generar cuerpo del correo en HTML
        $cuerpoHtml = generarCuerpoCorreo($info, $saldos, $fechaHoy);
        $totalCorreos += count($saldos);

        // Enviar correo a cada destinatario
        foreach ($destinatarios as $destinatario) {
            $totalCorreos++;
            $exito = enviarCorreo($fromEmail, $fromName, $destinatario, $info['asunto'], $cuerpoHtml);
            if ($exito) {
                $correosEnviados++;
                error_log("[OK] Correo enviado a {$destinatario} - " . $info['titulo'] . " (" . count($saldos) . " lotes)");
            } else {
                error_log("[ERROR] No se pudo enviar correo a {$destinatario} - " . $info['titulo']);
            }
        }

    } catch (PDOException $e) {
        error_log("[ERROR] Fallo al consultar prioridad '{$prioridad}': " . $e->getMessage());
    }
}

error_log("[INFO] Proceso completado. {$correosEnviados} correos enviados. {$totalCorreos} lotes reportados.");

// --- Funciones auxiliares ---

/**
 * Genera el cuerpo HTML del correo
 */
function generarCuerpoCorreo($info, $saldos, $fecha) {
    $totalLotes = count($saldos);
    $totalCajasFaltantes = array_sum(array_column($saldos, 'Cajas_Faltantes'));
    $promedioAvance = array_sum(array_map(function($s) {
        return isset($s['Porcentaje_Avance']) ? (float)str_replace(',', '.', $s['Porcentaje_Avance']) : 0;
    }, $saldos)) / $totalLotes;

    $html = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$info['asunto']}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: {$info['color']}; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; opacity: 0.9; font-size: 14px; }
        .content { padding: 20px 20px 10px; }
        .summary-box { background: {$info['color_fondo']}; border-left: 4px solid {$info['color']}; padding: 15px; margin: 10px 0 20px; border-radius: 0 4px 4px 0; }
        .summary-box h3 { margin: 0 0 5px; font-size: 16px; }
        .summary-box p { margin: 3px 0; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; font-size: 13px; }
        th { background: {$info['color']}; color: white; padding: 10px 8px; text-align: left; font-weight: 600; }
        td { border: 1px solid #e0e0e0; padding: 8px; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f0f8ff; }
        .critica { color: {$info['color']}; font-weight: bold; }
        .footer { background: #f0f0f0; padding: 15px 20px; border-radius: 0 0 8px 8px; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>{$info['titulo']}</h1>
            <p>{$fecha} | Sistema de Saldos GRC Packing</p>
        </div>

        <div class='content'>
            <p>{$info['descripcion']}</p>

            <div class='summary-box'>
                <h3>Resumen Ejecutivo</h3>
                <p><strong>Total lotes con esta prioridad:</strong> {$totalLotes}</p>
                <p><strong>Total cajas faltantes:</strong> {$totalCajasFaltantes}</p>
                <p><strong>Promedio de avance:</strong> " . number_format($promedioAvance, 1) . "%</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Empresa / Productor</th>
                        <th>Esp. / Var.</th>
                        <th>Folio</th>
                        <th>Embalaje</th>
                        <th>Cat. / Cal.</th>
                        <th align='center'>Act.</th>
                        <th align='center' class='critica'>Falt.</th>
                        <th align='center'>% Avan.</th>
                        <th align='center'>Días</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($saldos as $i => $fila) {
        $porcentaje = isset($fila['Porcentaje_Avance']) ? number_format(str_replace(',', '.', $fila['Porcentaje_Avance']), 1) : '0.0';

        // Destacar cajas faltantes críticas (>50)
        $claseFaltantes = ($fila['Cajas_Faltantes'] > 50) ? "class='critica'" : "";

        // Formatear productor
        $productor = !empty($fila['Productor_Real']) ? $fila['Productor_Real'] : 'N/A';

        $html .= "
            <tr>
                <td>{$fila['Empresa']}<br><small>{$productor}</small></td>
                <td>{$fila['Especie']} / {$fila['Variedad']}</td>
                <td>{$fila['Folio']}</td>
                <td>{$fila['Embalaje']}</td>
                <td>{$fila['Categoria']} / {$fila['Calibre']}</td>
                <td align='center'>{$fila['Cajas_Actuales']}</td>
                <td align='center' {$claseFaltantes}>{$fila['Cajas_Faltantes']}</td>
                <td align='center'>{$porcentaje}%</td>
                <td align='center'>{$fila['Dias_Embalaje']}</td>
                <td>{$fila['Accion_Sugerida']}</td>
            </tr>";
    }

    $html .= "
            </tbody>
        </table>

            <p><small><strong>Nota:</strong> Este reporte se genera automáticamente. Accione según la prioridad indicada y actualice el sistema tras cada movimiento de cajas.</small></p>
        </div>

        <div class='footer'>
            <p>Sistema de Saldos GRC Packing | {$fecha}</p>
            <p>GRCFRUIT SPA | Este correo es generado automáticamente - No responder</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

/**
 * Envía un correo electrónico
 */
function enviarCorreo($fromEmail, $fromName, $toEmail, $subject, $htmlBody) {
    // Headers
    $headers = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=UTF-8",
        "From: {$fromName} <{$fromEmail}>",
        "Reply-To: {$fromEmail}",
        "Return-Path: {$fromEmail}",
        "X-Mailer: PHP/" . phpversion(),
        "X-Priority: 1"  // Alta prioridad
    ];

    return mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
}
