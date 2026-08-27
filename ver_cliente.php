<?php
ob_start();
/* 24-08-2026  desde PC
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
$fechaPedido = '';
$totalPedido = 0;
$mensaje = '';
$tipoMensaje = '';
$mostrarSelectorPedido = $usuarioBuscado !== '';
$rutaFondo = __DIR__ . '/fotos/fondo.jpg';
$versionFondo = file_exists($rutaFondo) ? (string) filemtime($rutaFondo) : (string) time();
$fotosTelas = [];
$pesosTelas = [];
$pesosTelasPorArticulo = [];

function formatear_precio_mostrar($precio)
{
    $precioNumerico = convertir_precio_numerico($precio);

    return number_format($precioNumerico ?? 0, 0, ',', '.');
}

function formatear_precio_euro($precio)
{
    return "\xE2\x82\xAC " . formatear_precio_mostrar($precio);
}

function convertir_precio_numerico($precio)
{
    if (is_int($precio) || is_float($precio)) {
        return (float) $precio;
    }

    $texto = trim((string) $precio);
    if ($texto === '') {
        return null;
    }

    $texto = preg_replace('/[^0-9,.-]/', '', $texto);
    if ($texto === '' || !preg_match('/\d/', $texto)) {
        return null;
    }

    $ultimoPunto = strrpos($texto, '.');
    $ultimaComa = strrpos($texto, ',');
    if ($ultimoPunto !== false && $ultimaComa !== false) {
        $separadorDecimal = $ultimoPunto > $ultimaComa ? '.' : ',';
        $separadorMiles = $separadorDecimal === '.' ? ',' : '.';
        $texto = str_replace($separadorMiles, '', $texto);
        $texto = str_replace($separadorDecimal, '.', $texto);
    } elseif ($ultimoPunto !== false || $ultimaComa !== false) {
        $separador = $ultimoPunto !== false ? '.' : ',';
        $posicionSeparador = strrpos($texto, $separador);
        $digitosPosteriores = strlen($texto) - $posicionSeparador - 1;
        if ($digitosPosteriores === 3) {
            $texto = str_replace($separador, '', $texto);
        } else {
            $texto = str_replace($separador, '.', $texto);
        }
    }

    return is_numeric($texto) ? (float) $texto : null;
}

function es_precio_cero($precio)
{
    $precioNumerico = convertir_precio_numerico($precio);

    return $precioNumerico !== null && abs($precioNumerico) < 0.00001;
}

function obtener_precio_mostrar_item($precio, $tipo = 'producto')
{
    if ((string) $tipo === 'tela' && es_precio_cero($precio)) {
        return '';
    }

    return convertir_precio_numerico($precio) !== null ? formatear_precio_euro($precio) : (string) $precio;
}

function limpiar_nombre_producto_exportacion($producto, $precio = null)
{
    $texto = (string) $producto;
    $texto = preg_replace('/\s*\|\s*Rango\s*:\s*[^|]+/i', '', $texto);
    $texto = preg_replace('/\s*\|\s*Pagina\s*:\s*[^|]+/i', '', $texto);
    $texto = preg_replace('/\s*\|\s*Precio\s*:\s*[^|]+/i', '', $texto);

    $precioNumerico = convertir_precio_numerico($precio);
    if ($precioNumerico !== null) {
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

function obtener_precio_item($precio, $producto)
{
    if (preg_match('/(?:\||^)\s*Precio\s*:\s*([^|]+)/i', (string) $producto, $coincidencias)) {
        return trim($coincidencias[1]);
    }

    return trim((string) $precio);
}

function normalizar_nombre_archivo($texto)
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $texto);
    $texto = strtolower($texto ?: 'cliente');
    $texto = preg_replace('/[^a-z0-9]+/', '', $texto);

    return $texto !== '' ? $texto : 'cliente';
}

function normalizar_telefono_whatsapp($telefono)
{
    $telefono = preg_replace('/\D+/', '', (string) $telefono);
    if (substr($telefono, 0, 2) === '00') {
        $telefono = substr($telefono, 2);
    }
    if (substr($telefono, 0, 1) === '0') {
        $telefono = '58' . substr($telefono, 1);
    }

    return $telefono;
}

function obtener_dato_tela_producto($producto, $campo)
{
    $patron = '/(?:^|\|)\s*' . preg_quote($campo, '/') . '\s*:\s*([^|]*)/i';

    return preg_match($patron, (string) $producto, $coincidencias)
        ? trim($coincidencias[1])
        : '';
}

function crear_clave_tela($articulo, $muestrario, $composicion, $peso, $rango, $pagina)
{
    return implode('|', array_map(static function ($valor) {
        return strtolower(trim((string) $valor));
    }, [$articulo, $muestrario, $composicion, $peso, $rango, $pagina]));
}

function crear_clave_tela_sin_peso($articulo, $muestrario, $composicion, $rango, $pagina)
{
    return crear_clave_tela($articulo, $muestrario, $composicion, '', $rango, $pagina);
}

function crear_clave_tela_basica($articulo, $muestrario, $composicion)
{
    return crear_clave_tela($articulo, $muestrario, $composicion, '', '', '');
}

function actualizar_peso_tela_producto($producto, $peso)
{
    if (trim((string) $peso) === '') {
        return (string) $producto;
    }

    return preg_replace('/((?:^|\|)\s*Peso\s*:\s*)[^|]*/i', '$1' . trim((string) $peso), (string) $producto);
}

function resolver_ruta_foto_tela($foto)
{
    $foto = trim((string) $foto);
    if ($foto === '' || basename($foto) !== $foto) {
        return '';
    }

    $candidatos = pathinfo($foto, PATHINFO_EXTENSION) !== ''
        ? [$foto]
        : [$foto . '.jpeg', $foto . '.jpg', $foto . '.png'];

    foreach ($candidatos as $candidato) {
        if (is_file(__DIR__ . '/fotos/' . $candidato)) {
            return 'fotos/' . $candidato;
        }
    }

    return '';
}

$resultadoFotosTelas = mysqli_query($conexion, 'SELECT articulo, muestrario, composicion, pero, rango, pagina, foto FROM telas');
if ($resultadoFotosTelas) {
    while ($filaTela = mysqli_fetch_assoc($resultadoFotosTelas)) {
        $claveTela = crear_clave_tela(
            $filaTela['articulo'] ?? '',
            $filaTela['muestrario'] ?? '',
            $filaTela['composicion'] ?? '',
            $filaTela['pero'] ?? '',
            $filaTela['rango'] ?? '',
            $filaTela['pagina'] ?? ''
        );
        $fotosTelas[$claveTela] = resolver_ruta_foto_tela($filaTela['foto'] ?? '');
        $claveTelaSinPeso = crear_clave_tela_sin_peso(
            $filaTela['articulo'] ?? '',
            $filaTela['muestrario'] ?? '',
            $filaTela['composicion'] ?? '',
            $filaTela['rango'] ?? '',
            $filaTela['pagina'] ?? ''
        );
        $fotosTelas[$claveTelaSinPeso] = resolver_ruta_foto_tela($filaTela['foto'] ?? '');
        $pesosTelas[$claveTelaSinPeso] = trim((string) ($filaTela['pero'] ?? ''));
        $claveTelaBasica = crear_clave_tela_basica(
            $filaTela['articulo'] ?? '',
            $filaTela['muestrario'] ?? '',
            $filaTela['composicion'] ?? ''
        );
        $fotosTelas[$claveTelaBasica] = resolver_ruta_foto_tela($filaTela['foto'] ?? '');
        $pesosTelas[$claveTelaBasica] = trim((string) ($filaTela['pero'] ?? ''));
        $pesosTelasPorArticulo[strtolower(trim((string) ($filaTela['articulo'] ?? '')))] = trim((string) ($filaTela['pero'] ?? ''));
    }
    mysqli_free_result($resultadoFotosTelas);
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
                        'fecha' => (string) ($fila['fecha'] ?? ''),
                        'productos' => []
                    ];
                }

                $productoTela = (string) ($fila['producto'] ?? '');
                $articuloTelaPedido = obtener_dato_tela_producto($productoTela, 'Articulo');
                $muestrarioTelaPedido = obtener_dato_tela_producto($productoTela, 'Muestrario');
                $composicionTelaPedido = obtener_dato_tela_producto($productoTela, 'Composicion');
                $claveTelaPedido = crear_clave_tela_sin_peso(
                    $articuloTelaPedido,
                    $muestrarioTelaPedido,
                    $composicionTelaPedido,
                    obtener_dato_tela_producto($productoTela, 'Rango'),
                    obtener_dato_tela_producto($productoTela, 'Pagina')
                );
                $claveTelaBasicaPedido = crear_clave_tela_basica(
                    $articuloTelaPedido,
                    $muestrarioTelaPedido,
                    $composicionTelaPedido
                );

                $pedidosCliente[$clavePedido]['productos'][] = [
                    'producto' => $productoTela,
                    'precio' => obtener_precio_item($fila['precio'] ?? '', $fila['producto'] ?? ''),
                    'rango' => obtener_rango_producto($fila['producto'] ?? ''),
                    'tipo' => stripos($productoTela, 'Tela |') === 0 ? 'tela' : 'producto',
                    'pesoTela' => stripos($productoTela, 'Tela |') === 0
                        ? ($pesosTelas[$claveTelaPedido]
                            ?? $pesosTelas[$claveTelaBasicaPedido]
                            ?? $pesosTelasPorArticulo[strtolower($articuloTelaPedido)]
                            ?? obtener_dato_tela_producto($productoTela, 'Peso'))
                        : '',
                    'foto' => stripos($productoTela, 'Tela |') === 0
                        ? ($fotosTelas[$claveTelaPedido] ?? $fotosTelas[$claveTelaBasicaPedido] ?? '')
                        : ''
                ];
            }

            if ($pedidoSeleccionado !== '' && isset($pedidosCliente[$pedidoSeleccionado])) {
                $clienteMostrado = $pedidosCliente[$pedidoSeleccionado]['cliente'];
                $fechaPedido = $pedidosCliente[$pedidoSeleccionado]['fecha'];
                $productosPedido = $pedidosCliente[$pedidoSeleccionado]['productos'];
            } else {
                reset($pedidosCliente);
                $pedidoSeleccionado = (string) key($pedidosCliente);
                $pedidoData = current($pedidosCliente);
                $clienteMostrado = $pedidoData['cliente'];
                $fechaPedido = $pedidoData['fecha'];
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
        $precioNumerico = convertir_precio_numerico($precio);
        if ($precioNumerico !== null) {
            $totalPedido += $precioNumerico;
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
            : (convertir_precio_numerico($precio) ?? $precio);
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
    $productosPorRangoPdf = [];
    $ordenRangosPdf = [];
    foreach ($productosPedido as $item) {
        $rangoItem = (string) ($item['rango'] ?? obtener_rango_producto($item['producto'] ?? ''));
        if (!isset($productosPorRangoPdf[$rangoItem])) {
            $productosPorRangoPdf[$rangoItem] = [
                'telas' => [],
                'productos' => []
            ];
            $ordenRangosPdf[] = $rangoItem;
        }

        $tipoItem = ($item['tipo'] ?? 'producto') === 'tela' ? 'telas' : 'productos';
        $productosPorRangoPdf[$rangoItem][$tipoItem][] = $item;
    }

    foreach ($ordenRangosPdf as $rangoItem) {
        $filasProductosPdf .= '<tr class="rango"><td colspan="2">&nbsp;</td></tr>';
        $itemsDelRango = array_merge(
            $productosPorRangoPdf[$rangoItem]['telas'],
            $productosPorRangoPdf[$rangoItem]['productos']
        );

        foreach ($itemsDelRango as $item) {
            $precio = obtener_precio_item($item['precio'] ?? '', $item['producto'] ?? '');
            $precioMostrar = obtener_precio_mostrar_item($precio, $item['tipo'] ?? 'producto');
            $nombreProductoPdf = limpiar_nombre_producto_exportacion($item['producto'] ?? '', $precio);
            $filasProductosPdf .= '<tr><td>' . $escaparPdf($nombreProductoPdf) . '</td><td class="precio">' . $escaparPdf($precioMostrar) . '</td></tr>';
        }
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
        .productos .rango td { border-top: 2px solid #0f766e; border-bottom: 0; padding: 8px; color: #0f766e; font-weight: bold; background: #eef6f4; }
        .total td { font-weight: bold; background: #e3f3ef; }
    </style></head><body>';
    $nombreClientePdf = (string) ($clienteMostrado['nombre'] ?? $usuarioBuscado);
    $fechaPedidoPdf = $fechaPedido !== ''
        ? date('d/m/Y', strtotime($fechaPedido))
        : 'vacio';
    $htmlPdf .= '<h1>Pedido de ' . $escaparPdf($nombreClientePdf) . '</h1><table class="datos">';
    foreach ([
        'ID cliente' => $clienteMostrado['ID cliente'] ?? 'vacio',
        'Nombre' => $clienteMostrado['nombre'] ?? '',
        'Usuario' => $clienteMostrado['usuario'] ?? '',
        'Telefono' => $clienteMostrado['telefono'] ?? 'vacio',
        'Direccion' => $clienteMostrado['direccion'] ?? 'vacio',
        'Correo' => $clienteMostrado['correo'] ?? 'vacio',
        'Pedido' => $pedidoSeleccionado,
        'Fecha del pedido' => $fechaPedidoPdf
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
        $pdf->SetTitle('Pedido de ' . $nombreClientePdf);
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
            padding: 0 clamp(12px, 2.6vw, 24px);
            overflow-x: hidden;
            overflow-y: auto;
        }

        body::before {
            display: none;
        }

        .contenedor {
            width: min(900px, 100%);
            background: rgba(255, 255, 255, 0.3);
            border: 1px solid var(--borde);
            border-radius: 16px;
            padding: 0 28px 28px;
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
            color: #1f2937;
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

        .boton-archivo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
        }

        .boton-archivo img {
            display: block;
            width: 36px;
            height: 36px;
            object-fit: contain;
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
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .fila-dato {
            display: grid;
            grid-template-columns: minmax(140px, 220px) 1fr;
            gap: 10px;
            padding: 2px 4px;
            background: #f5f9ff;
            border: 1px solid #dde7f5;
            border-radius: 8px;
            color: #1f2937;
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
            color: #1f2937;
        }

        .foto-tela {
            display: block;
            width: 72px;
            height: 72px;
            object-fit: contain;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #ffffff;
        }

        .boton-foto-tela {
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
        }

        .visor-foto-tela {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: rgba(15, 23, 42, 0.8);
            cursor: zoom-out;
        }

        .visor-foto-tela.visible {
            display: flex;
        }

        .visor-foto-tela img {
            max-width: min(92vw, 900px);
            max-height: 90dvh;
            object-fit: contain;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
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
                padding: 0 18px 18px;
                border-radius: 12px;
            }

            .campo {
                min-width: 100%;
            }

            .fila-dato {
                grid-template-columns: 1fr;
            }

            .resultado {
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
            <h1>Detalle de cliente<?php echo is_array($clienteMostrado) && trim((string) ($clienteMostrado['nombre'] ?? '')) !== '' ? ' - ' . htmlspecialchars((string) $clienteMostrado['nombre'], ENT_QUOTES, 'UTF-8') : ''; ?></h1>

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
                    <a class="boton" href="seleccion_producto.php<?php echo $usuarioBuscado !== '' ? '?usuario=' . urlencode($usuarioBuscado) . '&pedido=nuevo' : ''; ?>">Nuevo pedido</a>
                    <?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                        <a class="boton" href="seleccion_producto.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>">Modificar pedido</a>
                        <button class="boton boton-peligro" type="submit" form="formEliminarPedido" onclick="return confirm('Se eliminara todo el pedido seleccionado. Desea continuar?');">Eliminar pedido</button>
                    <?php endif; ?>
                    <?php if ($usuarioBuscado !== ''): ?>
                        <?php $telefonoWhatsApp = normalizar_telefono_whatsapp($clienteMostrado['telefono'] ?? ''); ?>
                        <?php if ($telefonoWhatsApp !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                            <a class="boton boton-whatsapp boton-archivo" href="WhatsApp.php?<?php echo htmlspecialchars(http_build_query(['usuario' => $usuarioBuscado, 'pedido' => $pedidoSeleccionado, 'telefono' => $telefonoWhatsApp]), ENT_QUOTES, 'UTF-8'); ?>"><img src="fotos/wa.jpg" alt="WA"></a>
                        <?php endif; ?>
                        <a class="boton" href="editar_cliente.php?usuario=<?php echo urlencode($usuarioBuscado); ?>">Editar cliente</a>
                        <button class="boton boton-peligro" type="submit" form="formEliminarCliente" onclick="return confirm('ATENCION: Se eliminara completamente el cliente y TODOS sus pedidos. Desea continuar?');">Eliminar cliente</button>
                    <?php endif; ?>
                    <?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
                        <a class="boton boton-archivo" href="ver_cliente.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>&exportar_excel=1" download="<?php echo htmlspecialchars(normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado) . '_' . (int) $pedidoSeleccionado . '.xlsx', ENT_QUOTES, 'UTF-8'); ?>"><img src="fotos/xls.jpg" alt="Guardar Excel"></a>
                        <a class="boton boton-archivo" href="ver_cliente.php?usuario=<?php echo urlencode($usuarioBuscado); ?>&pedido=<?php echo urlencode($pedidoSeleccionado); ?>&exportar_pdf=1&v=<?php echo urlencode((string) filemtime(__FILE__)); ?>" download="<?php echo htmlspecialchars(normalizar_nombre_archivo($clienteMostrado['nombre'] ?? $usuarioBuscado) . '_' . (int) $pedidoSeleccionado . '.pdf', ENT_QUOTES, 'UTF-8'); ?>"><img src="fotos/pdf.jpg" alt="Guardar PDF"></a>
                        <a class="boton" href="envia_brioni.php?<?php echo htmlspecialchars(http_build_query(['usuario' => $usuarioBuscado, 'pedido' => $pedidoSeleccionado]), ENT_QUOTES, 'UTF-8'); ?>">Brioni</a>
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
                                    $precioProducto = obtener_precio_item($item['precio'] ?? '', $item['producto'] ?? '');
                                    $precioProductoMostrar = obtener_precio_mostrar_item($precioProducto, $item['tipo'] ?? 'producto');
                                    $pesoTelaMostrar = trim((string) ($item['pesoTela'] ?? ''));
                                    $productoMostrar = preg_replace('/\s*\|\s*Peso\s*:\s*[^|]*/i', '', (string) ($item['producto'] ?? ''));
                                    $nombreProductoMostrar = limpiar_nombre_producto_exportacion($productoMostrar, $precioProducto);
                                    if (($item['tipo'] ?? '') === 'tela' && $pesoTelaMostrar !== '') {
                                        $nombreProductoMostrar .= ' | Peso: ' . $pesoTelaMostrar;
                                    }
                                ?>
                                <div class="fila-producto">
                                    <div class="nombre-producto"><?php echo htmlspecialchars($nombreProductoMostrar, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="precio-producto">
                                        <?php if (($item['tipo'] ?? '') === 'tela' && ($item['foto'] ?? '') !== ''): ?>
                                            <button class="boton-foto-tela" type="button" data-foto-tela="<?php echo htmlspecialchars($item['foto'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="Ampliar foto de la tela">
                                                <img class="foto-tela" src="<?php echo htmlspecialchars($item['foto'], ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de la tela">
                                            </button>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($precioProductoMostrar, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
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
    <div class="visor-foto-tela" id="visorFotoTela" role="dialog" aria-modal="true" aria-label="Foto ampliada de la tela">
        <img id="fotoTelaAmpliada" src="" alt="Foto ampliada de la tela">
    </div>
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

        (function () {
            const visorFotoTela = document.getElementById('visorFotoTela');
            const fotoTelaAmpliada = document.getElementById('fotoTelaAmpliada');
            const botonesFotoTela = document.querySelectorAll('[data-foto-tela]');

            if (!visorFotoTela || !fotoTelaAmpliada || botonesFotoTela.length === 0) {
                return;
            }

            const cerrarVisor = function () {
                visorFotoTela.classList.remove('visible');
                fotoTelaAmpliada.removeAttribute('src');
            };

            botonesFotoTela.forEach(function (boton) {
                boton.addEventListener('click', function () {
                    fotoTelaAmpliada.src = boton.dataset.fotoTela;
                    visorFotoTela.classList.add('visible');
                });
            });

            visorFotoTela.addEventListener('click', function (event) {
                if (event.target === visorFotoTela) {
                    cerrarVisor();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    cerrarVisor();
                }
            });
        })();
    </script>
</body>
</html>