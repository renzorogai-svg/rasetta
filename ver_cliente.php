<?php
ob_start();

/* 22-08-2026  desde Laptop
    Archivo: ver_cliente.php
    Descripcion: Muestra los detalles de un cliente y sus pedidos.
*/ 
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

// Evita respuestas en cache para que los cambios de clientes/pedidos se reflejen al instante.
$marcaTemporal = gmdate('D, d M Y H:i:s') . ' GMT';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . $marcaTemporal);
header('ETag: "ver-cliente-' . md5($marcaTemporal . microtime(true)) . '"');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

$usuarioBuscado = trim($_GET['usuario'] ?? '');
$pedidoSeleccionado = trim($_GET['pedido'] ?? '');
$pedidosCliente = [];
$clienteMostrado = null;
$productosPedido = [];
$totalPedido = 0;
$mensaje = '';
$tipoMensaje = '';
$mostrarSelectorPedido = $usuarioBuscado !== '';
$rutaFondo = __DIR__ . '/fotos/fondo.jpg';
$versionFondo = file_exists($rutaFondo) ? (string) filemtime($rutaFondo) : (string) time();

function formatear_precio_mostrar($precio)
{
    return number_format((float) $precio, 0, ',', '.');
}

function formatear_precio_euro($precio)
{
    return '€ ' . formatear_precio_mostrar($precio);
}

function es_precio_cero($precio)
{
    return is_numeric($precio) && abs((float) $precio) < 0.00001;
}

function obtener_precio_mostrar_item($precio, $tipo = 'producto')
{
    if ((string) $tipo === 'tela' && es_precio_cero($precio)) {
        return '';
    }

    return is_numeric($precio) ? formatear_precio_euro($precio) : (string) $precio;
}

function limpiar_nombre_producto_exportacion($producto, $precio = null)
{
    $texto = (string) $producto;
    $texto = preg_replace('/\s*\|\s*Rango\s*:\s*[^|]+/i', '', $texto);
    $texto = preg_replace('/\s*\|\s*Pagina\s*:\s*[^|]+/i', '', $texto);

    if ($precio !== null && $precio !== '' && is_numeric($precio)) {
        $precioFormateado = (string) formatear_precio_mostrar($precio);
        $precioPlano = (string) (int) round((float) $precio);

        // Quita precios al final aunque vengan con separadores, prefijo '$' o luego de '|'.
        $texto = preg_replace('/\s*\|\s*\$?\s*' . preg_quote($precioFormateado, '/') . '\s*$/i', '', $texto);
        $texto = preg_replace('/\s*\|\s*\$?\s*' . preg_quote($precioPlano, '/') . '\s*$/i', '', $texto);
        $texto = preg_replace('/\s*\$?\s*' . preg_quote($precioFormateado, '/') . '\s*$/i', '', $texto);
        $texto = preg_replace('/\s*\$?\s*' . preg_quote($precioPlano, '/') . '\s*$/i', '', $texto);
    }

    $texto = preg_replace('/\s*\|\s*$/', '', $texto);
    $texto = preg_replace('/\s{2,}/', ' ', $texto);

    return trim($texto);
}

function obtener_rango_producto($producto)
{
    if (preg_match('/\|\s*Rango:\s*([^|]+)/i', (string) $producto, $coincidencias)) {
        return trim($coincidencias[1]);
    }

    return 'sin-rango';
}

function normalizar_nombre_archivo($texto)
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $texto);
    $texto = strtolower($texto ?: 'cliente');
    $texto = preg_replace('/[^a-z0-9]+/', '', $texto);

    return $texto !== '' ? $texto : 'cliente';
}

// Procesar eliminaci贸n de pedido individual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_pedido'])) {
    $usuarioEliminar = trim($_POST['usuario'] ?? '');
    $pedidoEliminar = trim($_POST['pedido'] ?? '');

    if ($usuarioEliminar === '' || !ctype_digit($pedidoEliminar) || (int) $pedidoEliminar <= 0) {
        $mensaje = 'No se pudo eliminar: datos del pedido invalidos.';
        $tipoMensaje = 'error';
    } else {
        $pedidoEliminarInt = (int) $pedidoEliminar;
        $stmtDelete = mysqli_prepare($conexion, 'DELETE FROM cliente WHERE usuario = ? AND pedido = ?');

        if ($stmtDelete) {
            mysqli_stmt_bind_param($stmtDelete, 'si', $usuarioEliminar, $pedidoEliminarInt);
            $okDelete = mysqli_stmt_execute($stmtDelete);
            $filasEliminadas = $okDelete ? mysqli_stmt_affected_rows($stmtDelete) : 0;
            mysqli_stmt_close($stmtDelete);

            if ($okDelete && $filasEliminadas > 0) {
                $mensaje = 'Pedido eliminado correctamente (' . $filasEliminadas . ' linea(s)).';
                $tipoMensaje = 'ok';
                $usuarioBuscado = $usuarioEliminar;
                $pedidoSeleccionado = '';
            } elseif ($okDelete) {
                $mensaje = 'No se encontraron lineas para eliminar en ese pedido.';
                $tipoMensaje = 'error';
                $usuarioBuscado = $usuarioEliminar;
                $pedidoSeleccionado = $pedidoEliminar;
            } else {
                $mensaje = 'No se pudo eliminar el pedido.';
                $tipoMensaje = 'error';
                $usuarioBuscado = $usuarioEliminar;
                $pedidoSeleccionado = $pedidoEliminar;
            }
        } else {
            $mensaje = 'Ocurrio un error al preparar la eliminacion.';
            $tipoMensaje = 'error';
            $usuarioBuscado = $usuarioEliminar;
            $pedidoSeleccionado = $pedidoEliminar;
        }
    }

    $mostrarSelectorPedido = $usuarioBuscado !== '';
}

// Procesar eliminaci贸n completa de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_cliente'])) {
    $usuarioEliminarCliente = trim($_POST['usuario'] ?? '');

    if ($usuarioEliminarCliente === '') {
        $mensaje = 'No se pudo eliminar: usuario invalido.';
        $tipoMensaje = 'error';
    } else {
        $stmtDeleteCliente = mysqli_prepare($conexion, 'DELETE FROM cliente WHERE usuario = ?');

        if ($stmtDeleteCliente) {
            mysqli_stmt_bind_param($stmtDeleteCliente, 's', $usuarioEliminarCliente);
            $okDeleteCliente = mysqli_stmt_execute($stmtDeleteCliente);
            $filasEliminadasCliente = $okDeleteCliente ? mysqli_stmt_affected_rows($stmtDeleteCliente) : 0;
            mysqli_stmt_close($stmtDeleteCliente);

            if ($okDeleteCliente && $filasEliminadasCliente > 0) {
                $mensaje = 'Cliente "' . $usuarioEliminarCliente . '" eliminado correctamente (' . $filasEliminadasCliente . ' registro(s) borrados).';
                $tipoMensaje = 'ok';
                $usuarioBuscado = '';
                $pedidoSeleccionado = '';
                $tokenCacheInicio = 'v' . preg_replace('/\D/', '', (string) microtime(true)) . random_int(1000, 9999);
                header('Location: inicio.php?' . $tokenCacheInicio);
                exit;
            } elseif ($okDeleteCliente) {
                $mensaje = 'No se encontraron registros para ese cliente.';
                $tipoMensaje = 'error';
            } else {
                $mensaje = 'No se pudo eliminar el cliente.';
                $tipoMensaje = 'error';
            }
        } else {
            $mensaje = 'Ocurrio un error al preparar la eliminacion del cliente.';
            $tipoMensaje = 'error';
        }
    }

    $mostrarSelectorPedido = $usuarioBuscado !== '';
}

if ($pedidoSeleccionado === 'nuevo' && $usuarioBuscado !== '') {
    header('Location: seleccion_producto.php?usuario=' . urlencode($usuarioBuscado) . '&pedido=nuevo');
    exit;
}

if ($usuarioBuscado === '') {
    if ($mensaje === '') {
        $mensaje = 'No se recibio un usuario para buscar.';
    }
} else {
    $stmt = mysqli_prepare($conexion, 'SELECT * FROM cliente WHERE usuario = ? ORDER BY pedido ASC, Id ASC');

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $usuarioBuscado);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            while ($fila = mysqli_fetch_assoc($resultado)) {
                foreach (['ID cliente', 'telefono', 'direccion', 'correo'] as $columnaCliente) {
                    if (trim((string) ($fila[$columnaCliente] ?? '')) === '') {
                        $fila[$columnaCliente] = 'vacio';
                    }
                }

                $clavePedido = (string) ($fila['pedido'] ?? '');
                if ($clavePedido === '') {
                    $clavePedido = 'sin_pedido_' . (string) ($fila['Id'] ?? uniqid('', true));
                }

                if (!isset($pedidosCliente[$clavePedido])) {
                    $pedidosCliente[$clavePedido] = [
                        'cliente' => $fila,
                        'pedido' => (string) ($fila['pedido'] ?? $clavePedido),
                        'productos' => []
                    ];
                }

                $pedidosCliente[$clavePedido]['productos'][] = [
                    'producto' => (string) ($fila['producto'] ?? ''),
                    'precio' => (string) ($fila['precio'] ?? ''),
                    'rango' => obtener_rango_producto($fila['producto'] ?? ''),
                    'tipo' => stripos((string) ($fila['producto'] ?? ''), 'Tela |') === 0 ? 'tela' : 'producto'
                ];
            }

            if ($pedidoSeleccionado !== '' && isset($pedidosCliente[$pedidoSeleccionado])) {
                $clienteMostrado = $pedidosCliente[$pedidoSeleccionado]['cliente'];
                $productosPedido = $pedidosCliente[$pedidoSeleccionado]['productos'];
            } else {
                reset($pedidosCliente);
                $pedidoSeleccionado = (string) key($pedidosCliente);
                $pedidoData = current($pedidosCliente);
                $clienteMostrado = $pedidoData['cliente'];
                $productosPedido = $pedidoData['productos'];
            }
        } else {
            $mensaje = 'No se encontro un cliente con ese usuario.';
        }

        mysqli_stmt_close($stmt);
    } else {
        $mensaje = 'Ocurrio un error al preparar la consulta.';
    }
}

if (!empty($productosPedido)) {
    foreach ($productosPedido as $item) {
        $precio = $item['precio'] ?? 0;
        if (is_numeric($precio)) {
            $totalPedido += (float) $precio;
        }
    }
}

if (isset($_GET['exportar_excel']) && $_GET['exportar_excel'] === '1') {
    if (!is_array($clienteMostrado) || !ctype_digit($pedidoSeleccionado) || (int) $pedidoSeleccionado <= 0) {
        http_response_code(400);
        exit('Seleccione un pedido valido para exportar.');
    }

    $hojaCalculo = new Spreadsheet();
    $hoja = $hojaCalculo->getActiveSheet();
    $hoja->setTitle('Pedido ' . $pedidoSeleccionado);

    $hoja->fromArray([
        ['Detalle de cliente', ''],
        ['ID cliente', $clienteMostrado['ID cliente'] ?? 'vacio'],
        ['Nombre', $clienteMostrado['nombre'] ?? ''],
        ['Usuario', $clienteMostrado['usuario'] ?? ''],
        ['Telefono', $clienteMostrado['telefono'] ?? 'vacio'],
        ['Direccion', $clienteMostrado['direccion'] ?? 'vacio'],
        ['Correo', $clienteMostrado['correo'] ?? 'vacio'],
        ['Pedido', $pedidoSeleccionado],
        [],
        ['Producto', 'Precio']
    ], null, 'A1');

    $filaExcel = 11;
    foreach ($productosPedido as $item) {
        $precio = $item['precio'] ?? '';
        $precioExcel = ((string) ($item['tipo'] ?? 'producto') === 'tela' && es_precio_cero($precio))
            ? ''
            : (is_numeric($precio) ? (float) $precio : $precio);
        $nombreProductoExcel = limpiar_nombre_producto_exportacion($item['producto'] ?? '', $precio);
        $hoja->fromArray([[$nombreProductoExcel, $precioExcel]], null, 'A' . $filaExcel);
        $filaExcel++;
    }

    $hoja->fromArray([['Total', $totalPedido]], null, 'A' . $filaExcel);
    $hoja->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14);
    $hoja->mergeCells('A1:B1');
    $hoja->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('0F766E');
    $hoja->getStyle('A1:B1')->getFont()->getColor()->setARGB('FFFFFF');
    $hoja->getStyle('A2:A8')->getFont()->setBold(true);
    $hoja->getStyle('A10:B10')->getFont()->setBold(true);
    $hoja->getStyle('A10:B10')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('D9EDE9');
    $hoja->getStyle('A' . $filaExcel . ':B' . $filaExcel)->getFont()->setBold(true);
    $hoja->getStyle('A1:B' . $filaExcel)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    $hoja->getStyle('A10:B' . $filaExcel)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setARGB('B8C7C4');
    $hoja->getStyle('A11:A' . $filaExcel)->getAlignment()->setWrapText(true);
    $hoja->getColumnDimension('A')->setWidth(72);
    $hoja->getColumnDimension('B')->setWidth(18);
    $hoja->getStyle('B11:B' . $filaExcel)->getNumberFormat()->setFormatCode('"€ "#,##0');
    $hoja->freezePane('A11');
    $hoja->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $hoja->getPageSetup()->setFitToWidth(1);
    $hoja->getPageSetup()->setFitToHeight(0);
    $hoja->setSelectedCell('A1');

    $nombreCliente = normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado);
    $nombreArchivo = $nombreCliente . '_' . (int) $pedidoSeleccionado . '.xlsx';

    $archivoTemporal = tempnam(sys_get_temp_dir(), 'rasetta_excel_');
    if ($archivoTemporal === false) {
        throw new RuntimeException('No se pudo preparar el archivo de Excel.');
    }

    $escritor = new Xlsx($hojaCalculo);
    $escritor->save($archivoTemporal);
    $hojaCalculo->disconnectWorksheets();

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
    header('Content-Length: ' . filesize($archivoTemporal));
    header('Cache-Control: max-age=0');
    header('Content-Transfer-Encoding: binary');

    readfile($archivoTemporal);
    unlink($archivoTemporal);
    exit;
}

if (isset($_GET['exportar_pdf']) && $_GET['exportar_pdf'] === '1') {
    if (!is_array($clienteMostrado) || !ctype_digit($pedidoSeleccionado) || (int) $pedidoSeleccionado <= 0) {
        http_response_code(400);
        exit('Seleccione un pedido valido para exportar.');
    }

    $escaparPdf = static function ($valor): string {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    };

    $filasProductosPdf = '';
    foreach ($productosPedido as $item) {
        $precio = $item['precio'] ?? '';
        $precioMostrar = obtener_precio_mostrar_item($precio, $item['tipo'] ?? 'producto');
        $nombreProductoPdf = limpiar_nombre_producto_exportacion($item['producto'] ?? '', $precio);
        $filasProductosPdf .= '<tr><td>' . $escaparPdf($nombreProductoPdf) . '</td><td class="precio">' . $escaparPdf($precioMostrar) . '</td></tr>';
    }

    $htmlPdf = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body { font-family: sans-serif; color: #1f2937; font-size: 10pt; }
        h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 8px; }
        h2 { font-size: 12pt; color: #0f766e; margin-top: 20px; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .datos td { border: 1px solid #d6dee8; padding: 6px 8px; }
        .datos td:first-child { width: 25%; font-weight: bold; background: #eef6f4; }
        .productos { width: 100%; border-collapse: collapse; }
        .productos th { background: #0f766e; color: #fff; text-align: left; padding: 7px 8px; }
        .productos td { border: 1px solid #d6dee8; padding: 7px 8px; vertical-align: top; }
        .productos .precio { width: 18%; text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; background: #e3f3ef; }
    </style></head><body>';
    $htmlPdf .= '<h1>Detalle de cliente y pedido</h1><table class="datos">';
    foreach ([
        'ID cliente' => $clienteMostrado['ID cliente'] ?? 'vacio',
        'Nombre' => $clienteMostrado['nombre'] ?? '',
        'Usuario' => $clienteMostrado['usuario'] ?? '',
        'Telefono' => $clienteMostrado['telefono'] ?? 'vacio',
        'Direccion' => $clienteMostrado['direccion'] ?? 'vacio',
        'Correo' => $clienteMostrado['correo'] ?? 'vacio',
        'Pedido' => $pedidoSeleccionado
    ] as $etiqueta => $valor) {
        $htmlPdf .= '<tr><td>' . $escaparPdf($etiqueta) . '</td><td>' . $escaparPdf($valor) . '</td></tr>';
    }
    $htmlPdf .= '</table><h2>Productos</h2><table class="productos"><thead><tr><th>Producto</th><th class="precio">Precio</th></tr></thead><tbody>';
    $htmlPdf .= $filasProductosPdf . '<tr class="total"><td>Total</td><td class="precio">' . $escaparPdf(formatear_precio_euro($totalPedido)) . '</td></tr></tbody></table></body></html>';

    try {
        $directorioTemporalPdf = __DIR__ . '/tmp';
        if (!is_dir($directorioTemporalPdf) && !mkdir($directorioTemporalPdf, 0775, true) && !is_dir($directorioTemporalPdf)) {
            throw new RuntimeException('No se pudo preparar la carpeta temporal del PDF.');
        }

        $archivoTemporalPdf = tempnam($directorioTemporalPdf, 'rasetta_pdf_');
        if ($archivoTemporalPdf === false) {
            throw new RuntimeException('No se pudo preparar el archivo PDF.');
        }

        $pdf = new Mpdf(['format' => 'A4', 'orientation' => 'P', 'tempDir' => $directorioTemporalPdf]);
        $pdf->SetTitle('Pedido ' . $pedidoSeleccionado);
        $pdf->WriteHTML($htmlPdf);
        $nombreCliente = normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado);
        $nombreArchivoPdf = $nombreCliente . '_' . (int) $pedidoSeleccionado . '.pdf';
        $pdf->Output($archivoTemporalPdf, \Mpdf\Output\Destination::FILE);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nombreArchivoPdf . '"');
        header('Content-Length: ' . filesize($archivoTemporalPdf));
        header('Cache-Control: max-age=0');

        readfile($archivoTemporalPdf);
        unlink($archivoTemporalPdf);
        exit;
    } catch (\Throwable $errorPdf) {
        file_put_contents(__DIR__ . '/pdf_error.log', date('c') . ' ' . $errorPdf->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        exit('No se pudo generar el PDF. Revise el archivo pdf_error.log.');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver cliente</title>
    <style>
        :root {
            --panel: #ffffff;
            --texto: #1f2937;
            --borde: #d6dee8;
            --acento: #0f766e;
            --acento-hover: #0b5f59;
            --sombra: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            position: relative;
            margin: 0;
            min-height: 100dvh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #e9eef9;
            color: var(--texto);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: clamp(12px, 2.6vw, 24px);
            overflow-x: hidden;
            overflow-y: auto;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url("fotos/fondo.jpg?v=<?php echo htmlspecialchars($versionFondo, ENT_QUOTES, 'UTF-8'); ?>") center center / contain no-repeat;
            z-index: -2;
        }

        .contenedor {
            width: min(900px, 100%);
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid var(--borde);
            border-radius: 16px;
            padding: 28px;
            box-shadow: var(--sombra);
            margin: 0 auto;
        }

        .tarjeta {
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 18px;
            background: #fbfdff;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        h1 {
            margin: 0;
            font-size: 1.4rem;
        }

        .formulario {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 240px;
            flex: 1;
        }

        label {
            font-size: 0.9rem;
            font-weight: 600;
        }

        input,
        select {
            padding: 9px 10px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 0.95rem;
            background: #ffffff;
        }

        .acciones-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            align-self: flex-start;
            text-align: center;
            text-decoration: none;
            background: var(--acento);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 8px 12px;
            border-radius: 10px;
            border: 0;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .boton:hover,
        .boton:focus-visible {
            background: var(--acento-hover);
        }

        .mensaje {
            margin: 0;
            font-weight: 600;
        }

        .mensaje.ok {
            color: #166534;
        }

        .mensaje.error {
            color: #b91c1c;
        }

        .boton-peligro {
            background: #b91c1c;
        }

        .boton-peligro:hover,
        .boton-peligro:focus-visible {
            background: #991b1b;
        }

        .resultado {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .fila-dato {
            display: grid;
            grid-template-columns: minmax(140px, 220px) 1fr;
            gap: 10px;
            padding: 8px 10px;
            background: #f5f9ff;
            border: 1px solid #dde7f5;
            border-radius: 8px;
        }

        .clave {
            font-weight: 700;
        }

        .valor {
            word-break: break-word;
        }

        .productos {
            display: grid;
            gap: 8px;
        }

        .encabezado-rango {
            margin-top: 8px;
            padding: 6px 2px;
            border-bottom: 1px solid #cbd5e1;
            color: var(--acento);
            font-weight: 700;
        }

        .encabezado-rango:first-child {
            margin-top: 0;
        }

        .fila-producto {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 8px 10px;
            background: #eef6ff;
            border: 1px solid #d9e8fb;
            border-radius: 8px;
        }

        .nombre-producto {
            font-weight: 600;
        }

        .precio-producto {
            font-weight: 700;
            text-align: right;
            white-space: nowrap;
        }

        .fila-total {
            background: #e3f3ef;
            border-color: #bfdfd8;
        }

        @media (max-width: 640px) {
            body::before {
                background-size: auto 100dvh;
                background-position: center top;
            }

            .contenedor {
                padding: 18px;
                border-radius: 12px;
            }

            .campo {
                min-width: 100%;
            }

            .fila-dato {
                grid-template-columns: 1fr;
            }

            .fila-producto {
                grid-template-columns: 1fr;
            }

            .precio-producto {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <main class="contenedor">
        <section class="tarjeta">
            <h1>Detalle de cliente</h1>

            <form class="formulario" method="get" action="ver_cliente.php">
                <div class="campo">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuarioBuscado, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ingrese el usuario" required>
                </div>

                <?php if ($mostrarSelectorPedido): ?>
                    <div class="campo">
                        <label for="pedido">Pedido</label>
                        <select id="pedido" name="pedido" onchange="this.form.submit()">
                            <option value="nuevo" <?php echo $pedidoSeleccionado === 'nuevo' ? 'selected' : ''; ?>>Nuevo pedido</option>
                            <?php foreach ($pedidosCliente as $clavePedido => $pedidoData): ?>
                                <option value="<?php echo htmlspecialchars((string) $clavePedido, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $pedidoSeleccionado === (string) $clavePedido ? 'selected' : ''; ?>>
                                    <?php echo 'Pedido ' . htmlspecialchars((string) ($pedidoData['pedido'] ?? $clavePedido), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="acciones-form">
                    <button class="boton" type="submit">Buscar</button>
                    <a class="boton" href="seleccion_producto.php<?php echo $usuarioBuscado !== '' ? '?usuario=' . urlencode($usuarioBuscado) . '&pedido=nuevo' : ''; ?>">Agregar pedido</a>
                    <?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                        <a class="boton" href="seleccion_producto.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>">Modificar pedido</a>
                        <a class="boton" href="ver_cliente.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>&exportar_excel=1" download="<?php echo htmlspecialchars(normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado) . '_' . (int) $pedidoSeleccionado . '.xlsx', ENT_QUOTES, 'UTF-8'); ?>">Guardar Excel</a>
                        <a class="boton" href="ver_cliente.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>&exportar_pdf=1&v=<?php echo urlencode((string) filemtime(__FILE__)); ?>" download="<?php echo htmlspecialchars(normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado) . '_' . (int) $pedidoSeleccionado . '.pdf', ENT_QUOTES, 'UTF-8'); ?>">Guardar PDF</a>
                    <?php endif; ?>
                    <?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                        <button class="boton boton-peligro" type="submit" form="formEliminarPedido" onclick="return confirm('Se eliminara todo el pedido seleccionado. Desea continuar?');">Eliminar pedido</button>
                    <?php endif; ?>
                    <?php if ($usuarioBuscado !== ''): ?>
                        <button class="boton boton-peligro" type="submit" form="formEliminarCliente" onclick="return confirm('ATENCION: Se eliminara completamente el cliente y TODOS sus pedidos. Desea continuar?');">Eliminar cliente</button>
                    <?php endif; ?>
                    <a class="boton" id="btnVolverInicio" href="inicio.php">Volver</a>
                </div>
            </form>

            <?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                <form id="formEliminarPedido" method="post" action="ver_cliente.php">
                    <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuarioBuscado, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="pedido" value="<?php echo htmlspecialchars($pedidoSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="eliminar_pedido" value="1">
                </form>
            <?php endif; ?>

            <?php if ($usuarioBuscado !== ''): ?>
                <form id="formEliminarCliente" method="post" action="ver_cliente.php">
                    <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuarioBuscado, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="eliminar_cliente" value="1">
                </form>
            <?php endif; ?>

            <?php if ($mensaje !== ''): ?>
                <p class="mensaje <?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if (is_array($clienteMostrado)): ?>
                <div class="resultado">
                    <?php foreach ($clienteMostrado as $columna => $valor): ?>
                        <?php if (in_array((string) $columna, ['Id', 'producto', 'precio'], true)): continue; endif; ?>
                        <div class="fila-dato">
                            <div class="clave"><?php echo htmlspecialchars((string) $columna, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="valor"><?php echo htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($productosPedido)): ?>
                    <div class="productos">
                        <?php
                            $productosPorRango = [];
                            $ordenRangos = [];

                            foreach ($productosPedido as $item) {
                                $rangoItem = (string) ($item['rango'] ?? 'sin-rango');
                                if (!isset($productosPorRango[$rangoItem])) {
                                    $productosPorRango[$rangoItem] = [
                                        'telas' => [],
                                        'productos' => []
                                    ];
                                    $ordenRangos[] = $rangoItem;
                                }

                                $tipoItem = ($item['tipo'] ?? 'producto') === 'tela' ? 'telas' : 'productos';
                                $productosPorRango[$rangoItem][$tipoItem][] = $item;
                            }

                            foreach ($ordenRangos as $rangoItem):
                                $itemsDelRango = array_merge(
                                    $productosPorRango[$rangoItem]['telas'],
                                    $productosPorRango[$rangoItem]['productos']
                                );
                        ?>
                            <div class="encabezado-rango">
                                <?php echo htmlspecialchars($rangoItem === 'sin-rango' ? 'Sin rango' : 'Rango ' . $rangoItem, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php foreach ($itemsDelRango as $item): ?>
                                <?php
                                    $precioProducto = $item['precio'] ?? '';
                                    $precioProductoMostrar = obtener_precio_mostrar_item($precioProducto, $item['tipo'] ?? 'producto');
                                ?>
                                <div class="fila-producto">
                                    <div class="nombre-producto"><?php echo htmlspecialchars((string) ($item['producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="precio-producto"><?php echo htmlspecialchars($precioProductoMostrar, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <div class="fila-producto fila-total">
                            <div class="nombre-producto">Total</div>
                            <div class="precio-producto"><?php echo htmlspecialchars(formatear_precio_euro($totalPedido), ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>
    <script>
        (function () {
            const btnVolverInicio = document.getElementById('btnVolverInicio');
            if (!btnVolverInicio) {
                return;
            }

            btnVolverInicio.addEventListener('click', function (event) {
                event.preventDefault();
                const marca = 'v' + Date.now().toString() + Math.floor(Math.random() * 100000).toString();
                window.location.href = 'inicio.php?' + marca;
            });
        })();
    </script>
</body>
</html>