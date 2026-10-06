<?php
/**
 * Script: dashboard_saldos_packing.php
 * Descripción: Genera y envía un dashboard resumido de saldos de packing
 *              basado en la vista vista_saldos_packing, usando SMTP.
 *
 * El dashboard se enfoca en análisis ejecutivo: no incluye folios individuales,
 * sino resúmenes por empresa, especie/variedad, rango de edad del embalaje y prioridad.
 *
 * Cron: Diario a las 08:00 (lunes a viernes)
 * Uso: php dashboard_saldos_packing.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Configuración de correo desde .env
$fromEmail = $_ENV['SMTP_USER'] ?? 'alertas@grcfruit.cl';
$fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Dashboard GRC Packing';
$fechaHoy = date('d/m/Y');
$horaActual = date('H:i');

// --- Destinatario ---
$destinatario = "rperez@grcfruit.cl";

// === CONSULTAS DE DATOS AGRUPADOS ===

try {
    // 1. Resumen general (KPIs)
    $sqlGeneral = "
        SELECT
            COUNT(*) as total_lotes,
            SUM(Cajas_Actuales) as total_cajas_actuales,
            SUM(Cajas_Faltantes) as total_cajas_faltantes,
            AVG(Porcentaje_Avance) as promedio_avance,
            MAX(Dias_Embalaje) as max_dias_embalaje
        FROM vista_saldos_packing
    ";
    $stmt = $pdo->query($sqlGeneral);
    $kpi = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Saldo por empresa
    $sqlEmpresa = "
        SELECT
            Empresa,
            COUNT(*) as total_lotes,
            SUM(Cajas_Faltantes) as cajas_faltantes,
            AVG(Porcentaje_Avance) as promedio_avance,
            MAX(Dias_Embalaje) as max_dias
        FROM vista_saldos_packing
        GROUP BY Empresa
        ORDER BY SUM(Cajas_Faltantes) DESC
    ";
    $stmt = $pdo->query($sqlEmpresa);
    $saldosEmpresa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Saldo por prioridad
    $sqlPrioridad = "
        SELECT
            Prioridad,
            COUNT(*) as total_lotes,
            SUM(Cajas_Faltantes) as cajas_faltantes,
            MIN(Dias_Embalaje) as min_dias,
            MAX(Dias_Embalaje) as max_dias
        FROM vista_saldos_packing
        GROUP BY Prioridad
        ORDER BY SUM(Cajas_Faltantes) DESC
    ";
    $stmt = $pdo->query($sqlPrioridad);
    $saldosPrioridad = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Saldo por rango de edad
    $sqlRango = "
        SELECT
            CASE
                WHEN Dias_Embalaje <= 3 THEN '0-3 dias'
                WHEN Dias_Embalaje <= 7 THEN '4-7 dias'
                WHEN Dias_Embalaje <= 14 THEN '8-14 dias'
                WHEN Dias_Embalaje <= 30 THEN '15-30 dias'
                ELSE '+30 dias'
            END as rango_edad,
            COUNT(*) as total_lotes,
            SUM(Cajas_Faltantes) as cajas_faltantes
        FROM vista_saldos_packing
        GROUP BY rango_edad
        ORDER BY MIN(Dias_Embalaje)
    ";
    $stmt = $pdo->query($sqlRango);
    $saldosRango = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Saldos antiguos (>7 días) - datos críticos
    $sqlAntiguos = "
        SELECT
            Empresa,
            Productor_Real,
            Especie,
            Variedad,
            Embalaje,
            Calibre,
            Cajas_Faltantes,
            Dias_Embalaje,
            Accion_Sugerida
        FROM vista_saldos_packing
        WHERE Dias_Embalaje > 7
        ORDER BY Dias_Embalaje DESC
        LIMIT 20
    ";
    $stmt = $pdo->query($sqlAntiguos);
    $saldosAntiguos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Productores con mayor cantidad de cajas faltantes
    $sqlProductor = "
        SELECT
            Productor_Real,
            COUNT(*) as total_lotes,
            SUM(Cajas_Faltantes) as cajas_faltantes,
            MAX(Dias_Embalaje) as max_dias
        FROM vista_saldos_packing
        GROUP BY Productor_Real
        ORDER BY SUM(Cajas_Faltantes) DESC
        LIMIT 10
    ";
    $stmt = $pdo->query($sqlProductor);
    $saldosProductor = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("[ERROR] Fallo en consultas: " . $e->getMessage());
    exit(1);
}

// === GENERAR DASHBOARD HTML ===
$html = generarDashboard($kpi, $saldosEmpresa, $saldosPrioridad, $saldosRango, $saldosAntiguos, $saldosProductor, $fechaHoy, $horaActual);

// === ENVIAR CORREO ===
$asunto = "Dashboard Saldos Packing GRC - {$fechaHoy}";
$exito = enviarCorreoSMTP($fromEmail, $fromName, $destinatario, $asunto, $html);

if ($exito) {
    error_log("[OK] Dashboard enviado a {$destinatario} - Fecha: {$fechaHoy}");
} else {
    error_log("[ERROR] No se pudo enviar el dashboard a {$destinatario}");
}

exit(0);

// === FUNCIÓN: GENERAR DASHBOARD ===
function generarDashboard($kpi, $saldosEmpresa, $saldosPrioridad, $saldosRango, $saldosAntiguos, $saldosProductor, $fecha, $hora) {
    $html = "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Dashboard Saldos Packing GRC</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background: #f0f2f5; color: #333; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1a237e 0%, #283593 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0 0; opacity: 0.85; font-size: 14px; }
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .kpi-card { background: white; border-radius: 8px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .kpi-value { font-size: 28px; font-weight: bold; color: #1a237e; margin: 0; }
        .kpi-label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px; }
        .kpi-sub { font-size: 12px; color: #999; }
        .section { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .section h2 { margin: 0 0 15px; color: #1a237e; font-size: 16px; border-bottom: 2px solid #e8eaf6; padding-bottom: 10px; }
        .table-responsive { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; font-size: 13px; }
        th { background: #1a237e; color: white; padding: 10px; text-align: left; font-weight: 600; }
        td { border: 1px solid #e8eaf6; padding: 8px 10px; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .progress-cell { position: relative; }
        .progress-bar { background: #e0e0e0; border-radius: 10px; height: 20px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 10px; }
        .badge-critical { background: #c62828; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-warning { background: #e65100; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-info { background: #1565c0; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-success { background: #2e7d32; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        .highlight { color: #c62828; font-weight: bold; }
        .highlight-warn { color: #e65100; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <!-- HEADER -->
        <div class='header'>
            <h1>Dashboard Saldos Packing GRC Fruit</h1>
            <p>Fecha: {$fecha} | Hora: {$hora} | Temporada 2026</p>
        </div>

        <!-- KPIs -->
        <div class='kpi-grid'>
            <div class='kpi-card'>
                <div class='kpi-value'>{$kpi['total_lotes']}</div>
                <div class='kpi-label'>Lotes en saldo</div>
            </div>
            <div class='kpi-card'>
                <div class='kpi-value'>{$kpi['total_cajas_actuales']}</div>
                <div class='kpi-label'>Cajas colocadas</div>
            </div>
            <div class='kpi-card'>
                <div class='kpi-value highlight'>{$kpi['total_cajas_faltantes']}</div>
                <div class='kpi-label'>Cajas faltantes</div>
            </div>
            <div class='kpi-card'>
                <div class='kpi-value'>{$kpi['promedio_avance']} %</div>
                <div class='kpi-label'>Avance promedio</div>
            </div>
        </div>

        <!-- EMPRESAS CON SALDOS -->
        <div class='section'>
            <h2>Resumen por Empresa</h2>
            <table>
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th align='center'>Lotes</th>
                        <th align='center'>Cajas Faltantes</th>
                        <th align='center'>Días Máx.</th>
                        <th align='center'>Avance</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($saldosEmpresa as $empresa) {
        $colorBarra = 'background: #4caf50;';
        if ($empresa['promedio_avance'] < 30) $colorBarra = 'background: #c62828;';
        elseif ($empresa['promedio_avance'] < 50) $colorBarra = 'background: #ff9800;';

        $html .= "
                    <tr>
                        <td>{$empresa['Empresa']}</td>
                        <td align='center'>{$empresa['total_lotes']}</td>
                        <td align='center' class='highlight'>{$empresa['cajas_faltantes']}</td>
                        <td align='center'>{$empresa['max_dias']}</td>
                        <td align='center'>
                            <div class='progress-bar' style='max-width:100px; display:inline-block;'>
                                <div class='progress-fill' style='width:{$empresa['promedio_avance']}%; {$colorBarra};'></div>
                            </div>
                            <span>{$empresa['promedio_avance']}%</span>
                        </td>
                    </tr>";
    }

    $html .= "
                </tbody>
            </table>
        </div>

        <!-- PRIORIDADES -->
        <div class='section'>
            <h2>Saldos por Prioridad</h2>
            <table>
                <thead>
                    <tr>
                        <th>Prioridad</th>
                        <th align='center'>Lotes</th>
                        <th align='center'>Cajas Faltantes</th>
                        <th align='center'>Rango Días</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($saldosPrioridad as $prioridad) {
        $prioridadClean = str_replace(['?', "\xe2\x9c\x93", "\xe2\x9c\x97"], '', $prioridad['Prioridad']);
        $prioridadClean = preg_replace('/[^\x20-\x7E\xC0-\xFF]/', '', $prioridadClean); // Remove emojis
        $prioridadClean = trim($prioridadClean);

        $badge = '';
        if (preg_match('/CRITICO/i', $prioridad['Prioridad'])) {
            $badge = '<span class="badge-critical">CRITICO</span>';
        } elseif (preg_match('/REPA/i', $prioridad['Prioridad'])) {
            $badge = '<span class="badge-warning">URGENTE</span>';
        } elseif (preg_match('/COMPLETAR/i', $prioridad['Prioridad'])) {
            $badge = '<span class="badge-info">SEGUIMIENTO</span>';
        } elseif (preg_match('/EN CURSO/i', $prioridad['Prioridad'])) {
            $badge = '<span class="badge-success">EN CURSO</span>';
        }

        $html .= "
                    <tr>
                        <td>{$badge} {$prioridadClean}</td>
                        <td align='center'>{$prioridad['total_lotes']}</td>
                        <td align='center' class='highlight'>{$prioridad['cajas_faltantes']}</td>
                        <td align='center'>{$prioridad['min_dias']} - {$prioridad['max_dias']} dias</td>
                    </tr>";
    }

    $html .= "
                </tbody>
            </table>
        </div>

        <!-- RANGO DE EDAD -->
        <div class='section'>
            <h2>Distribución por Edad de Embalaje</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rango de Edad</th>
                        <th align='center'>Lotes</th>
                        <th align='center'>Cajas Faltantes</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($saldosRango as $rango) {
        $bgColor = '#e8f5e9';
        if (strpos($rango['rango_edad'], '8-14') !== false) $bgColor = '#fff3e0';
        if (strpos($rango['rango_edad'], '15-30') !== false) $bgColor = '#ffebee';
        if (strpos($rango['rango_edad'], '+30') !== false) $bgColor = '#ffebee';

        $html .= "
                    <tr style='background:{$bgColor};'>
                        <td>{$rango['rango_edad']}</td>
                        <td align='center'>{$rango['total_lotes']}</td>
                        <td align='center'>{$rango['cajas_faltantes']}</td>
                    </tr>";
    }

    $html .= "
                </tbody>
            </table>
        </div>

        <!-- TOP PRODUCTORES -->
        <div class='section'>
            <h2>Top 10 Productores con mas Cajas Faltantes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Productor</th>
                        <th align='center'>Lotes</th>
                        <th align='center'>Cajas Faltantes</th>
                        <th align='center'>Dias Max.</th>
                    </tr>
                </thead>
                <tbody>";

    foreach ($saldosProductor as $productor) {
        $html .= "
                    <tr>
                        <td>{$productor['Productor_Real']}</td>
                        <td align='center'>{$productor['total_lotes']}</td>
                        <td align='center' class='highlight'>{$productor['cajas_faltantes']}</td>
                        <td align='center'>{$productor['max_dias']}</td>
                    </tr>";
    }

    $html .= "
                </tbody>
            </table>
        </div>";

    // 5. Saldo antiguo - alertas
    if (count($saldosAntiguos) > 0) {
        $html .= "
        <!-- ALERTAS DE SALDOS ANTIGUOS -->
        <div class='section'>
            <h2>Saldos con Mas de 7 Dias de Antiguedad</h2>
            <p style='margin-top:0;'><strong>" . count($saldosAntiguos) . " lotes</strong> requieren atención urgente.</p>
            <table>
                <thead>
                    <tr>
                        <th>Empresa / Productor</th>
                        <th>Esp. / Var.</th>
                        <th>Embalaje</th>
                        <th>Calibre</th>
                        <th align='center'>Faltantes</th>
                        <th align='center'>Días</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($saldosAntiguos as $antiguo) {
            $html .= "
                    <tr>
                        <td>{$antiguo['Empresa']}<br><small>{$antiguo['Productor_Real']}</small></td>
                        <td>{$antiguo['Especie']} / {$antiguo['Variedad']}</td>
                        <td>{$antiguo['Embalaje']}</td>
                        <td>{$antiguo['Calibre']}</td>
                        <td align='center' class='highlight'>{$antiguo['Cajas_Faltantes']}</td>
                        <td align='center' class='badge-critical'>{$antiguo['Dias_Embalaje']}</td>
                        <td>{$antiguo['Accion_Sugerida']}</td>
                    </tr>";
        }

        $html .= "
                </tbody>
            </table>
        </div>";
    }

    // FOOTER
    $html .= "
        <div class='footer'>
            <p>Dashboard GRC Packing - Generado automaticamente | {$fecha} {$hora}</p>
            <p>GRCFRUIT SPA | alertas@grcfruit.cl</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}

// === FUNCIÓN: ENVIAR CORREO CON SMTP ===
function enviarCorreoSMTP($fromEmail, $fromName, $toEmail, $subject, $htmlBody) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.grcfruit.cl';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'] ?? 'alertas@grcfruit.cl';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? 'tls';
        $mail->Port       = (int)($_ENV['SMTP_PORT'] ?? 587);

        // Destinatarios
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        $mail->addReplyTo($fromEmail);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("[ERROR] PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
