<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarja de Pallet - Duplicada</title>
    <style>
        /* Ajustamos márgenes de la hoja para maximizar el espacio */
        @page { margin: 15px 25px; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 11px; 
            color: #000;
            margin: 0;
            padding: 0;
        }

        .bold { font-weight: bold; }
        .text-center { text-align: center; }
        .text-lg { font-size: 13px; }
        .text-xl { font-size: 17px; }
        .m-0 { margin: 0; }

        /* Contenedor de cada copia para forzar que queden simétricas */
        .tarja-seccion {
            width: 100%;
            height: 47%; /* Altura justa para que entren dos copias en un A4 */
            box-sizing: border-box;
        }
        
        /* Línea discontinua que le indica al operario dónde cortar con la guillotina */
        .linea-corte {
            border-top: 1px dashed #000;
            margin: 15px 0;
            width: 100%;
            clear: both;
        }

        /* Tablas de Estructura */
        .layout-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .layout-table td { padding: 2px; vertical-align: top; }

        /* Tabla de Datos Principal */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; text-align: center; font-size: 11px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 5px 3px; }
        .data-table th { background-color: #f2f2f2; }

        /* Recuadro del Total de Cajas */
        .resumen-box {
            border: 2px solid #000;
            padding: 4px;
            text-align: center;
            width: 120px;
            float: right;
        }

        /* Simulación del Código de Barras (Code 39) */
        .barcode {
            font-family: 'Courier New', Courier, monospace; 
            font-size: 20px;
            text-align: center;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        
        .clear { clear: both; }
    </style>
</head>
<body>

<?php
// 1. Mapeamos las variables desde el registro único recuperado ($result)
$fila = $result; 

$folio     = $fila['folio'] ?? '';
$especie   = $fila['especie'] ?? '';
$variedad  = $fila['variedad'] ?? '';
$etiqueta  = !empty($fila['etiqueta']) ? $fila['etiqueta'] : 'NA';
$empresa   = $fila['empresa'] ?? '';
$planta    = '121293'; 
$productor = $fila['productor'] ?? '';
$categoria = $fila['categoria'] ?? '';
$peso_neto = isset($fila['kilos']) ? number_format($fila['kilos'], 2, ',', '.') : '0,00';
$embalaje  = $fila['embalaje'] ?? '';
$calibre   = $fila['calibre'] ?? '';
$sdp       = $fila['sdp'] ?? '';
$cajas     = $fila['cajas'] ?? '0';

if ($empresa === 'FMS SPA') {
    $categoria = 'CAT 1';
}

// Formateamos la fecha de embalaje limpiamente
$fecha_raw = $fila['fecha embalaje'] ?? $fila['fecha'] ?? '';
if (!empty($fecha_raw)) {
    $fecha_objeto = new DateTime($fecha_raw);
    $fecha_embalaje = $fecha_objeto->format('d-m-Y H:i');
} else {
    $fecha_embalaje = '';
}

// 2. EJECUTAMOS EL BUCLE DOS VECES (Copia 1 y Copia 2)
for ($copia = 1; $copia <= 2; $copia++):
?>
    <div class="tarja-seccion">
        
        <table class="layout-table">
            <tr>
                <td width="33%">
                    <span class="bold">ESPECIE</span><br>
                    <span class="text-lg bold"><?php echo htmlspecialchars($especie); ?></span>
                </td>
                <td width="33%">
                    <span class="bold">VARIEDAD</span><br>
                    <span class="text-lg bold"><?php echo htmlspecialchars($variedad); ?></span>
                </td>
                <td width="33%">
                    <span class="bold">ETIQUETA</span><br>
                    <span class="text-lg bold"><?php echo htmlspecialchars($etiqueta); ?></span>
                </td>
            </tr>
        </table>

        <hr style="border: 0; border-top: 1px solid #000; margin: 3px 0 8px 0;">

        <table class="layout-table">
            <tr>
                <td width="33%">
                    <span class="bold">PLANTA</span><br>
                    <span class="text-xl bold"><?php echo htmlspecialchars($planta); ?></span>
                </td>
                <td width="33%">
                    <span class="bold">FOLIO</span><br>
                    <span class="text-xl bold"><?php echo htmlspecialchars($folio); ?></span>
                </td>
                <td width="33%" style="vertical-align: middle;">
                    <span class="bold text-lg"><?php echo htmlspecialchars($empresa); ?></span>
                </td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Folio Origen</th>
                    <th>Productor</th>
                    <th>Categoría</th>
                    <th>Peso Neto</th>
                    <th>Embalaje</th>
                    <th>Fecha Embalaje</th>
                    <th>Calibre</th>
                    <th>sdp</th>
                    <th>Cajas</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($folio); ?></td>
                    <td><?php echo htmlspecialchars($productor); ?></td>
                    <td><?php echo htmlspecialchars($categoria); ?></td>
                    <td><?php echo htmlspecialchars($peso_neto); ?></td>
                    <td><?php echo htmlspecialchars($embalaje); ?></td>
                    <td><?php echo htmlspecialchars($fecha_embalaje); ?></td>
                    <td><?php echo htmlspecialchars($calibre); ?></td>
                    <td><?php echo htmlspecialchars($sdp); ?></td>
                    <td><?php echo htmlspecialchars($cajas); ?></td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 5px;">
            <div class="resumen-box">
                <span class="bold" style="font-size: 10px;">Total Cajas</span><br>
                <span class="text-xl bold"><?php echo number_format($cajas, 2, ',', '.'); ?></span>
            </div>

            <div style="float: left; width: 60%; padding-top: 3px;">
                <div class="barcode">
                    *<?php echo htmlspecialchars($folio); ?>*
                </div>
            </div>
            <div class="clear"></div>
        </div>

    </div>

<?php 
    // Si acaba de terminar de dibujar la primera copia, inyectamos la línea de puntos
    if ($copia === 1) {
        echo '<div class="linea-corte"></div>';
    }
endfor; 
?>

</body>
</html>