<?php
/*
29-08-2026  desde Laptop
Archivo: seleccion_producto.php
Descripción: Página para seleccionar productos y telas para un pedido de un cliente.
*/
require_once __DIR__ . '/conexion.php';

$usuarioSeleccionado = trim($_GET['usuario'] ?? ($_POST['usuario'] ?? ''));
$pedidoSeleccionado = trim($_GET['pedido'] ?? ($_POST['pedido'] ?? ''));
$clienteSeleccionado = null;
$numeroPedidoNuevo = null;
$mensajePedido = '';
$tipoMensajePedido = '';
$productosPedidoActual = [];
$productosSeleccionadosMapa = [];
$esEdicionPedidoExistente = false;

function producto_marcado_en_pedido($textoProducto, array $productosSeleccionadosMapa, array $productosPedidoActual)
{
	if (isset($productosSeleccionadosMapa[$textoProducto])) {
		return true;
	}

	foreach ($productosPedidoActual as $itemPedidoActual) {
		$productoGuardado = trim((string) ($itemPedidoActual['producto'] ?? ''));
		if ($productoGuardado !== '' && (strpos($textoProducto, $productoGuardado) === 0 || strpos($productoGuardado, $textoProducto) === 0)) {
			return true;
		}
	}

	return false;
}

function producto_marcado_por_prefijo($prefijoProducto, array $productosPedidoActual)
{
	foreach ($productosPedidoActual as $itemPedidoActual) {
		$productoGuardado = trim((string) ($itemPedidoActual['producto'] ?? ''));
		if ($productoGuardado !== '' && strpos($productoGuardado, $prefijoProducto) === 0) {
			return true;
		}
	}

	return false;
}

function formatear_precio_mostrar($precio)
{
	return number_format((float) $precio, 0, ',', '.');
}

function limpiar_traduccion_titulo($titulo)
{
	return trim((string) preg_replace('/\s*\([^)]*\)/', '', (string) $titulo));
}

if ($usuarioSeleccionado !== '') {
	$stmtCliente = mysqli_prepare($conexion, 'SELECT `ID cliente`, nombre, usuario, telefono, direccion, correo FROM cliente WHERE usuario = ? ORDER BY Id ASC LIMIT 1');

	if ($stmtCliente) {
		mysqli_stmt_bind_param($stmtCliente, 's', $usuarioSeleccionado);
		mysqli_stmt_execute($stmtCliente);
		$resultadoCliente = mysqli_stmt_get_result($stmtCliente);

		if ($resultadoCliente && mysqli_num_rows($resultadoCliente) > 0) {
			$filaCliente = mysqli_fetch_assoc($resultadoCliente);
			$clienteSeleccionado = [
				'id_cliente' => trim((string) ($filaCliente['ID cliente'] ?? '')) ?: 'vacio',
				'nombre' => (string) ($filaCliente['nombre'] ?? ''),
				'usuario' => (string) ($filaCliente['usuario'] ?? $usuarioSeleccionado),
				'telefono' => trim((string) ($filaCliente['telefono'] ?? '')) ?: 'vacio',
				'direccion' => trim((string) ($filaCliente['direccion'] ?? '')) ?: 'vacio',
				'correo' => trim((string) ($filaCliente['correo'] ?? '')) ?: 'vacio'
			];
		}

		mysqli_stmt_close($stmtCliente);
	}
}

if ($usuarioSeleccionado !== '') {
	$stmtPedidos = mysqli_prepare($conexion, 'SELECT pedido FROM cliente WHERE usuario = ?');

	if ($stmtPedidos) {
		mysqli_stmt_bind_param($stmtPedidos, 's', $usuarioSeleccionado);
		mysqli_stmt_execute($stmtPedidos);
		$resultadoPedidos = mysqli_stmt_get_result($stmtPedidos);

		$maxPedido = 0;
		if ($resultadoPedidos) {
			while ($filaPedido = mysqli_fetch_assoc($resultadoPedidos)) {
				$pedidoActual = (int) ($filaPedido['pedido'] ?? 0);
				if ($pedidoActual > $maxPedido) {
					$maxPedido = $pedidoActual;
				}
			}
		}

		$pedidoRecibidoValido = ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0;
		$numeroPedidoNuevo = $pedidoRecibidoValido ? (int) $pedidoSeleccionado : ($maxPedido > 0 ? $maxPedido + 1 : 1);
		$pedidoSeleccionado = (string) $numeroPedidoNuevo;
		mysqli_stmt_close($stmtPedidos);
	}
}

if ($usuarioSeleccionado !== '' && $numeroPedidoNuevo !== null) {
	$stmtPedidoActual = mysqli_prepare($conexion, 'SELECT producto, precio FROM cliente WHERE usuario = ? AND pedido = ? ORDER BY Id ASC');

	if ($stmtPedidoActual) {
		mysqli_stmt_bind_param($stmtPedidoActual, 'si', $usuarioSeleccionado, $numeroPedidoNuevo);
		mysqli_stmt_execute($stmtPedidoActual);
		$resultadoPedidoActual = mysqli_stmt_get_result($stmtPedidoActual);

		if ($resultadoPedidoActual) {
			while ($filaPedidoActual = mysqli_fetch_assoc($resultadoPedidoActual)) {
				$productoPedidoActual = trim((string) ($filaPedidoActual['producto'] ?? ''));
				$precioPedidoActual = $filaPedidoActual['precio'] ?? 0;

				$productosPedidoActual[] = [
					'producto' => $productoPedidoActual,
					'precio' => is_numeric($precioPedidoActual) ? (int) round((float) $precioPedidoActual) : 0
				];

				if ($productoPedidoActual !== '') {
					$productosSeleccionadosMapa[$productoPedidoActual] = true;
				}
			}
		}

		$esEdicionPedidoExistente = !empty($productosPedidoActual);
		mysqli_stmt_close($stmtPedidoActual);
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pedido'])) {
	$itemsSeleccionadosJson = trim($_POST['items_seleccionados'] ?? '');

	if ($clienteSeleccionado === null || $numeroPedidoNuevo === null) {
		$mensajePedido = 'No se pudo determinar el cliente o el numero de pedido actual.';
		$tipoMensajePedido = 'error';
	} elseif ($itemsSeleccionadosJson === '' && !$esEdicionPedidoExistente) {
		$mensajePedido = 'Seleccione al menos un producto o una tela para guardar el pedido.';
		$tipoMensajePedido = 'error';
	} else {
		$itemsDecodificados = json_decode($itemsSeleccionadosJson, true);
		$itemsValidos = [];

		if (is_array($itemsDecodificados)) {
			foreach ($itemsDecodificados as $item) {
				if (!is_array($item)) {
					continue;
				}

				$producto = trim((string) ($item['producto'] ?? ''));
				$precioCrudo = $item['precio'] ?? 0;
				$precio = is_numeric($precioCrudo) ? (int) round((float) $precioCrudo) : 0;

				if ($producto === '') {
					continue;
				}

				$itemsValidos[] = [
					'producto' => $producto,
					'precio' => $precio
				];
			}
		}

		if (empty($itemsValidos) && !$esEdicionPedidoExistente) {
			$mensajePedido = 'No se encontraron productos validos para guardar.';
			$tipoMensajePedido = 'error';
		} else {
			$nombreCliente = (string) ($clienteSeleccionado['nombre'] ?? '');
			$usuarioCliente = (string) ($clienteSeleccionado['usuario'] ?? $usuarioSeleccionado);
			$idCliente = (string) ($clienteSeleccionado['id_cliente'] ?? 'vacio');
			$telefonoCliente = (string) ($clienteSeleccionado['telefono'] ?? 'vacio');
			$direccionCliente = (string) ($clienteSeleccionado['direccion'] ?? 'vacio');
			$correoCliente = (string) ($clienteSeleccionado['correo'] ?? 'vacio');
			$pedidoNuevoGuardar = (int) $numeroPedidoNuevo;
			$fechaPedido = date('Y-m-d');

			mysqli_begin_transaction($conexion);
			$guardadoOk = true;

			if ($esEdicionPedidoExistente) {
				$stmtDeleteActual = mysqli_prepare($conexion, 'DELETE FROM cliente WHERE usuario = ? AND pedido = ?');

				if (!$stmtDeleteActual) {
					$guardadoOk = false;
				} else {
					mysqli_stmt_bind_param($stmtDeleteActual, 'si', $usuarioCliente, $pedidoNuevoGuardar);
					$guardadoOk = mysqli_stmt_execute($stmtDeleteActual);
					mysqli_stmt_close($stmtDeleteActual);
				}
			}

			$sqlInsert = 'INSERT INTO cliente (`ID cliente`, nombre, usuario, telefono, direccion, correo, pedido, fecha, producto, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
			$stmtInsert = $guardadoOk ? mysqli_prepare($conexion, $sqlInsert) : false;

			if ($guardadoOk && !$stmtInsert && !empty($itemsValidos)) {
				$guardadoOk = false;
			}

			if (!$guardadoOk) {
				mysqli_rollback($conexion);
				$mensajePedido = 'Ocurrio un error al preparar el guardado del pedido.';
				$tipoMensajePedido = 'error';
			} else {
				foreach ($itemsValidos as $itemValido) {
					if (!$stmtInsert) {
						break;
					}

					$productoGuardar = $itemValido['producto'];
					$precioGuardar = $itemValido['precio'];

					mysqli_stmt_bind_param($stmtInsert, 'ssssssissi', $idCliente, $nombreCliente, $usuarioCliente, $telefonoCliente, $direccionCliente, $correoCliente, $pedidoNuevoGuardar, $fechaPedido, $productoGuardar, $precioGuardar);
					if (!mysqli_stmt_execute($stmtInsert)) {
						$guardadoOk = false;
						break;
					}
				}

				if ($stmtInsert) {
					mysqli_stmt_close($stmtInsert);
				}

				if ($guardadoOk) {
					mysqli_commit($conexion);
					if ($esEdicionPedidoExistente) {
						$mensajePedido = empty($itemsValidos)
							? 'Pedido actualizado: se eliminaron todos los productos.'
							: 'Pedido actualizado correctamente con ' . count($itemsValidos) . ' producto(s).';
					} else {
						$mensajePedido = 'Pedido guardado correctamente con ' . count($itemsValidos) . ' producto(s).';
					}
					$tipoMensajePedido = 'ok';
					$numeroPedidoNuevo = $pedidoNuevoGuardar;
					$pedidoSeleccionado = (string) $pedidoNuevoGuardar;
					$productosPedidoActual = $itemsValidos;
					$productosSeleccionadosMapa = [];

					foreach ($itemsValidos as $itemValido) {
						$productosSeleccionadosMapa[$itemValido['producto']] = true;
					}

					$esEdicionPedidoExistente = true;
					header('Location: inicio.php?v=' . urlencode((string) time()));
					exit;
				} else {
					mysqli_rollback($conexion);
					$mensajePedido = 'No se pudo guardar el pedido completo.';
					$tipoMensajePedido = 'error';
				}
			}
		}
	}
}

$productosObjetivo = [
	['db' => 'traje dos piezas', 'titulo' => '2 piece Suit (Traje Dos Piezas)'],
	['db' => 'traje tres piezas', 'titulo' => '3 piece Suit (Traje Tres Piezas)'],
	['db' => 'chaqueta', 'titulo' => 'Jacket (Chaqueta)']
];

$productosUnPrecio = [
	['db' => 'pantalones', 'titulo' => 'Trousers (Pantalones)'],
	['db' => 'chaleco', 'titulo' => 'Vest (Chaleco)'],
	['db' => 'esmoquin', 'titulo' => 'Tuxedo (Esmoquin)'],
	['db' => 'esmoquin tres piezas', 'titulo' => '3 Piece Tuxedo (Esmoquin Tres Piezas)'],
	['db' => 'esmoquin chaqueta', 'titulo' => 'Dinner Jacket(Esmoquin chaqueta)'],
	['db' => 'esmoquin pantalon', 'titulo' => 'Tuxedo Trousers (Esmoquin Pantalon)'],
	['db' => 'frac', 'titulo' => 'Tail Coat (Frac)'],
	['db' => 'cache', 'titulo' => 'Morning Coat Jacket (chaqueta de chaqué)'],
];

$datosPorProducto = [];
$datosUnPrecio = [];
$datosSobretodo = [];
$columnasAccesorios = [];
$datosAccesorios = [];
$errorAccesorios = '';
$datosTelas = [];
$datosTelasPorMuestrario = [];
$articulosTelasPorRango = [];

foreach ($productosObjetivo as $item) {
	$datosPorProducto[$item['db']] = [];
}

foreach ($productosUnPrecio as $item) {
	$datosUnPrecio[$item['db']] = [];
}

$sql = "SELECT Id, producto, rango, unboton, dosbotones, especial
		FROM ropadia
		WHERE producto IN ('traje dos piezas', 'traje tres piezas', 'chaqueta')
		ORDER BY FIELD(producto, 'traje dos piezas', 'traje tres piezas', 'chaqueta'), rango, Id";

$resultado = mysqli_query($conexion, $sql);

if ($resultado) {
	while ($fila = mysqli_fetch_assoc($resultado)) {
		$claveProducto = strtolower(trim($fila['producto']));
		if (array_key_exists($claveProducto, $datosPorProducto)) {
			$datosPorProducto[$claveProducto][] = $fila;
		}
	}
	mysqli_free_result($resultado);
} else {
	die('Error en la consulta: ' . mysqli_error($conexion));
}

$sqlUnPrecio = "SELECT Id, producto, rango, precio
				FROM unprecio
				WHERE producto IN ('pantalones', 'chaleco', 'esmoquin', 'esmoquin tres piezas', 'esmoquin chaqueta', 'esmoquin pantalon', 'frac', 'cache')
				ORDER BY FIELD(producto, 'pantalones', 'chaleco', 'esmoquin', 'esmoquin tres piezas', 'esmoquin chaqueta', 'esmoquin pantalon', 'frac', 'cache'), rango, Id";
$resultadoUnPrecio = mysqli_query($conexion, $sqlUnPrecio);

if ($resultadoUnPrecio) {
	while ($filaUnPrecio = mysqli_fetch_assoc($resultadoUnPrecio)) {
		$productoClave = strtolower(trim($filaUnPrecio['producto']));
		if (array_key_exists($productoClave, $datosUnPrecio)) {
			$datosUnPrecio[$productoClave][] = $filaUnPrecio;
		}
	}
	mysqli_free_result($resultadoUnPrecio);
} else {
	die('Error en la consulta de filas unprecio: ' . mysqli_error($conexion));
}

$sqlSobretodo = "SELECT Id, rango, categoria1, categoria2, categoria3
				 FROM sobretodo
				 ORDER BY rango, Id";
$resultadoSobretodo = mysqli_query($conexion, $sqlSobretodo);

if ($resultadoSobretodo) {
	while ($filaSobretodo = mysqli_fetch_assoc($resultadoSobretodo)) {
		$datosSobretodo[] = $filaSobretodo;
	}
	mysqli_free_result($resultadoSobretodo);
} else {
	die('Error en la consulta de filas sobretodo: ' . mysqli_error($conexion));
}

$resultadoColumnasAccesorios = mysqli_query($conexion, "SHOW COLUMNS FROM accesorios");

if ($resultadoColumnasAccesorios) {
	while ($columnaAccesorio = mysqli_fetch_assoc($resultadoColumnasAccesorios)) {
		$columnasAccesorios[] = (string) ($columnaAccesorio['Field'] ?? '');
	}
	mysqli_free_result($resultadoColumnasAccesorios);

	$resultadoAccesorios = mysqli_query($conexion, "SELECT * FROM accesorios");
	if ($resultadoAccesorios) {
		while ($filaAccesorio = mysqli_fetch_assoc($resultadoAccesorios)) {
			$datosAccesorios[] = $filaAccesorio;
		}
		mysqli_free_result($resultadoAccesorios);
	} else {
		$errorAccesorios = 'No se pudieron leer los datos de accesorios: ' . mysqli_error($conexion);
	}
} else {
	$errorAccesorios = 'No se pudo leer la estructura de accesorios: ' . mysqli_error($conexion);
}

$sqlTelas = "SELECT Id, articulo, muestrario, composicion, pero, rango, pagina, foto
			 FROM telas
			 ORDER BY articulo, muestrario, rango, Id";
$resultadoTelas = mysqli_query($conexion, $sqlTelas);

if ($resultadoTelas) {
	while ($filaTela = mysqli_fetch_assoc($resultadoTelas)) {
		$datosTelas[] = $filaTela;
		$claveMuestrario = trim((string) ($filaTela['muestrario'] ?? ''));
		if ($claveMuestrario === '') {
			$claveMuestrario = 'Sin muestrario';
		}

		if (!array_key_exists($claveMuestrario, $datosTelasPorMuestrario)) {
			$datosTelasPorMuestrario[$claveMuestrario] = [];
		}

		$datosTelasPorMuestrario[$claveMuestrario][] = $filaTela;
		$rangoTela = trim((string) ($filaTela['rango'] ?? ''));
		$articuloTela = trim((string) ($filaTela['articulo'] ?? ''));
		if ($rangoTela !== '' && $articuloTela !== '') {
			if (!isset($articulosTelasPorRango[$rangoTela])) {
				$articulosTelasPorRango[$rangoTela] = [];
			}

			if (!in_array($articuloTela, $articulosTelasPorRango[$rangoTela], true)) {
				$articulosTelasPorRango[$rangoTela][] = $articuloTela;
			}
		}
	}
	mysqli_free_result($resultadoTelas);
} else {
	die('Error en la consulta de telas: ' . mysqli_error($conexion));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Seleccionar producto</title>
	<style>
		:root {
			--fondo: #f4f6f8;
			--panel: #ffffff;
			--texto: #1f2937;
			--muted: #6b7280;
			--borde: #d1d5db;
			--encabezado: #0f766e;
			--encabezado-texto: #ffffff;
			--fila-hover: #ecfeff;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			min-height: 100dvh;
			padding: clamp(12px, 2.6vw, 24px);
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
			background: linear-gradient(180deg, #e6fffa 0%, var(--fondo) 35%, #eef2ff 100%);
			color: var(--texto);
			overflow-x: hidden;
		}

		.contenedor {
			max-width: 1200px;
			margin: 0 auto;
			background: var(--panel);
			border: 1px solid #e5e7eb;
			border-radius: 16px;
			padding: 24px;
			box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
		}

		h1 {
			margin: 0 0 8px;
			font-size: 1.8rem;
			text-align: center;
			letter-spacing: 0.4px;
		}

		.subtitulo {
			margin: 0 0 18px;
			text-align: center;
			color: var(--muted);
		}

		.titulo-seccion-productos {
			margin: 22px 0 10px;
			padding: 12px 16px;
			border-left: 4px solid #0f766e;
			background: #f0fdfa;
			color: #115e59;
			font-size: 1.2rem;
			font-weight: 700;
		}

		.datos-cliente {
			margin-bottom: 18px;
			padding: 16px;
			border: 1px solid #d1fae5;
			border-radius: 12px;
			background: #f0fdf4;
		}

		.datos-cliente h2 {
			margin: 0 0 12px;
			font-size: 1.05rem;
			color: #065f46;
		}

		.grilla-datos {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
			gap: 10px;
		}

		.item-dato {
			padding: 10px 12px;
			border-radius: 10px;
			background: #ffffff;
			border: 1px solid #c7f0da;
		}

		.item-dato .clave {
			font-size: 0.78rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.04em;
			color: #4b5563;
			margin-bottom: 4px;
		}

		.item-dato .valor {
			font-size: 0.95rem;
			word-break: break-word;
		}

		.acciones {
			display: flex;
			justify-content: flex-end;
			margin-bottom: 14px;
		}

		.boton-inicio {
			display: inline-block;
			padding: 8px 12px;
			border-radius: 8px;
			text-decoration: none;
			font-size: 0.92rem;
			font-weight: 600;
			background: #0f766e;
			color: #ffffff;
		}

		.boton-inicio:hover,
		.boton-inicio:focus-visible {
			background: #0b5f59;
		}

		.bloque-tabla {
			margin-bottom: 6px;
			border: 1px solid var(--borde);
			border-radius: 12px;
			overflow: hidden;
		}

		.bloque-tabla:last-child {
			margin-bottom: 0;
		}

		.acordeon-linea {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			padding: 14px 16px;
			border: 0;
			background: #f8fafc;
			color: #115e59;
			font-size: 1.08rem;
			font-weight: 600;
			cursor: pointer;
			text-align: left;
		}

		.acordeon-telas {
			background: #d0bfbf;
			color: #0d1813;
		}

		.acordeon-linea:focus-visible {
			outline: 2px solid #0f766e;
			outline-offset: -2px;
		}

		.acordeon-flecha {
			font-size: 0.85rem;
			transform: rotate(0deg);
			transition: transform 0.2s ease;
			flex: 0 0 auto;
		}

		.bloque-tabla.abierta .acordeon-flecha {
			transform: rotate(180deg);
		}

		.tabla-wrapper {
			width: 100%;
			overflow-x: auto;
			display: none;
			border-top: 1px solid var(--borde);
		}

		.bloque-tabla.abierta > .tabla-wrapper {
			display: block;
		}

		table {
			width: 100%;
			border-collapse: collapse;
			min-width: 700px;
			background: #fff;
		}

		thead th {
			background: var(--encabezado);
			color: var(--encabezado-texto);
			text-align: left;
			padding: 10px 12px;
			font-size: 0.9rem;
			letter-spacing: 0.2px;
		}

		tbody td {
			border-top: 1px solid #e5e7eb;
			padding: 9px 12px;
			font-size: 0.92rem;
		}

		tbody tr:hover {
			background: var(--fila-hover);
		}

		.sin-datos {
			border: 1px dashed #94a3b8;
			border-radius: 10px;
			padding: 12px;
			font-size: 0.95rem;
			color: #334155;
			background: #f8fafc;
		}

		.col-seleccion {
			width: 90px;
			text-align: center;
		}

		.cell-check {
			text-align: center;
		}

		.precio-opcion {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			white-space: nowrap;
		}

		.check-producto {
			width: 16px;
			height: 16px;
			cursor: pointer;
		}

		.panel-pedido {
			margin: 18px 0 20px;
			padding: 14px;
			border: 1px solid #cbd5e1;
			border-radius: 12px;
			background: #f8fafc;
		}

		.panel-pedido h2 {
			margin: 0 0 10px;
			font-size: 1rem;
			color: #0f172a;
		}

		#listaPedido {
			margin: 0;
			padding-left: 20px;
		}

		#listaPedido li {
			margin: 6px 0;
		}

		#listaPedido li.item-pedido {
			display: flex;
			justify-content: space-between;
			align-items: baseline;
			gap: 16px;
		}

		#listaPedido li.item-pedido .precio-item {
			margin-left: auto;
			white-space: nowrap;
		}

		#listaPedido li.encabezado-rango {
			margin: 14px 0 6px;
			padding-bottom: 4px;
			border-bottom: 1px solid #cbd5e1;
			list-style: none;
			font-weight: 700;
			color: #0f766e;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		#listaPedido li.encabezado-rango:first-child {
			margin-top: 0;
		}

		#listaPedido li.encabezado-rango .rango-tela {
			font-weight: 400;
		}

		.btn-quitar-item {
			background: transparent;
			border: none;
			color: #ef4444;
			font-size: 1.25rem;
			font-weight: 700;
			cursor: pointer;
			padding: 0 6px;
			margin-left: 8px;
			line-height: 1;
			border-radius: 4px;
			transition: background 0.15s, color 0.15s;
		}

		.btn-quitar-item:hover,
		.btn-quitar-item:focus-visible {
			background: #fee2e2;
			color: #b91c1c;
		}

		.total-pedido {
			margin-top: 12px;
			padding-top: 12px;
			border-top: 1px solid #cbd5e1;
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 12px;
			font-weight: 700;
			color: #0f172a;
		}

		.lista-vacia {
			margin: 0;
			color: #64748b;
		}

		.mensaje-pedido {
			margin: 0 0 10px;
			font-weight: 700;
		}

		.mensaje-pedido.ok {
			color: #166534;
		}

		.mensaje-pedido.error {
			color: #b91c1c;
		}

		.acciones-pedido {
			margin-top: 12px;
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
		}

		.boton-guardar {
			display: inline-block;
			padding: 9px 14px;
			border: 0;
			border-radius: 10px;
			font-size: 0.92rem;
			font-weight: 700;
			cursor: pointer;
			background: #0f766e;
			color: #ffffff;
		}

		.boton-guardar:hover,
		.boton-guardar:focus-visible {
			background: #0b5f59;
		}

		.boton-guardar:disabled {
			cursor: not-allowed;
			opacity: 0.55;
		}

		.boton-finalizar {
			display: inline-block;
			padding: 9px 14px;
			border-radius: 10px;
			font-size: 0.92rem;
			font-weight: 700;
			text-decoration: none;
			background: #334155;
			color: #ffffff;
		}

		.boton-finalizar:hover,
		.boton-finalizar:focus-visible {
			background: #1e293b;
		}

		.mini-foto-tela {
			max-width: 70px;
			max-height: 70px;
			border-radius: 8px;
			display: block;
			cursor: zoom-in;
		}

		.modal-imagen {
			position: fixed;
			inset: 0;
			display: none;
			align-items: center;
			justify-content: center;
			padding: 24px;
			background: rgba(15, 23, 42, 0.82);
			z-index: 1000;
		}

		.modal-imagen.abierto {
			display: flex;
		}

		.modal-imagen-contenido {
			position: relative;
			max-width: min(92vw, 1100px);
			max-height: 88vh;
		}

		.modal-imagen-foto {
			display: block;
			max-width: 100%;
			max-height: 88vh;
			border-radius: 14px;
			box-shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
		}

		.modal-imagen-cerrar {
			position: absolute;
			top: -12px;
			right: -12px;
			width: 36px;
			height: 36px;
			border: 0;
			border-radius: 999px;
			background: #ffffff;
			color: #0f172a;
			font-size: 1.2rem;
			font-weight: 700;
			cursor: pointer;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
		}

		@media (max-width: 640px) {
			.contenedor {
				padding: 16px;
				border-radius: 12px;
			}

			.acordeon-linea {
				padding: 12px;
				font-size: 0.98rem;
			}

			.total-pedido {
				flex-direction: column;
				align-items: flex-start;
			}
		}
	</style>
</head>
<body>
	<main class="contenedor">
		<div class="acciones">
			<a class="boton-inicio" href="ver_cliente.php<?php echo $usuarioSeleccionado !== '' ? '?usuario=' . urlencode($usuarioSeleccionado) : ''; ?>">Volver al cliente</a>
		</div>
		<h1>Seleccionar producto</h1>
		<p class="subtitulo"><?php echo $esEdicionPedidoExistente ? 'Modifique el pedido seleccionado agregando o quitando productos y telas.' : 'Elija un producto para agregarlo al pedido del cliente.'; ?></p>

		<?php if ($clienteSeleccionado !== null): ?>
			<section class="datos-cliente">
				<h2>Datos del pedido en curso</h2>
				<div class="grilla-datos">
					<div class="item-dato">
						<div class="clave">Nombre</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
					<div class="item-dato">
						<div class="clave">Usuario</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['usuario'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
					<div class="item-dato">
						<div class="clave">ID cliente</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['id_cliente'] ?? 'vacio'), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
					<div class="item-dato">
						<div class="clave">Teléfono</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['telefono'] ?? 'vacio'), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
					<div class="item-dato">
						<div class="clave">Dirección</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['direccion'] ?? 'vacio'), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
					<div class="item-dato">
						<div class="clave">Correo</div>
						<div class="valor"><?php echo htmlspecialchars((string) ($clienteSeleccionado['correo'] ?? 'vacio'), ENT_QUOTES, 'UTF-8'); ?></div>
					</div>

					<?php if ($numeroPedidoNuevo !== null): ?>
						<div class="item-dato">
							<div class="clave">Pedido</div>
							<div class="valor"><?php echo htmlspecialchars((string) $numeroPedidoNuevo, ENT_QUOTES, 'UTF-8'); ?></div>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php elseif ($usuarioSeleccionado !== ''): ?>
			<p class="subtitulo">No se encontraron datos para este usuario.</p>
		<?php endif; ?>

		<section class="panel-pedido">
			<h2>Lista telas y productos seleccionados</h2>
			<?php if ($mensajePedido !== ''): ?>
				<p class="mensaje-pedido <?php echo htmlspecialchars($tipoMensajePedido, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensajePedido, ENT_QUOTES, 'UTF-8'); ?></p>
			<?php endif; ?>
			<p id="mensajeListaVacia" class="lista-vacia">Aun no hay productos seleccionados.</p>
			<ul id="listaPedido"></ul>
			<div class="total-pedido">
				<span>Total del pedido</span>
				<span id="totalPedidoValor">0</span>
			</div>
			<form method="post" action="seleccion_producto.php<?php echo $usuarioSeleccionado !== '' ? '?usuario=' . urlencode($usuarioSeleccionado) : ''; ?>">
				<input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuarioSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" name="pedido" value="<?php echo htmlspecialchars($pedidoSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
				<input type="hidden" id="modoEdicionPedido" value="<?php echo $esEdicionPedidoExistente ? '1' : '0'; ?>">
				<input type="hidden" id="itemsSeleccionadosInput" name="items_seleccionados" value="">
				<div class="acciones-pedido">
					<button type="submit" name="guardar_pedido" id="botonGuardarPedido" class="boton-guardar" disabled>Guardar y volver</button>
				</div>
			</form>
		</section>

		<section class="bloque-tabla abierta">
			<div class="acordeon-linea acordeon-telas" role="button" tabindex="0" aria-expanded="true">
				<strong>Telas</strong>
				<span class="acordeon-flecha">▼</span>
			</div>
			<?php if (!empty($datosTelasPorMuestrario)): ?>
				<div class="tabla-wrapper">
					<?php foreach ($datosTelasPorMuestrario as $nombreMuestrario => $filasMuestrario): ?>
						<section class="bloque-tabla">
							<div class="acordeon-linea acordeon-telas" role="button" tabindex="0" aria-expanded="false">
								<span><?php echo htmlspecialchars('Muestrario: ' . $nombreMuestrario, ENT_QUOTES, 'UTF-8'); ?></span>
								<span class="acordeon-flecha">▼</span>
							</div>
							<div class="tabla-wrapper">
								<table>
									<thead>
										<tr>
											<th>Articulo</th>
											<th>Muestrario</th>
											<th>Composicion</th>
											<th>Peso</th>
											<th>Rango</th>
											<th>Pagina</th>
											<th>Foto</th>
											<th class="col-seleccion">Agregar</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($filasMuestrario as $filaTela): ?>
											<?php
												$articuloTela = trim((string) ($filaTela['articulo'] ?? 'Sin articulo'));
												$muestrarioTela = trim((string) ($filaTela['muestrario'] ?? '-'));
												$composicionTela = trim((string) ($filaTela['composicion'] ?? '-'));
												$pesoTela = trim((string) ($filaTela['pero'] ?? '-'));
												$rangoTela = trim((string) ($filaTela['rango'] ?? '-'));
												$paginaTela = trim((string) ($filaTela['pagina'] ?? '-'));
												$textoTela = 'Tela | Articulo: ' . $articuloTela . ' | Muestrario: ' . $muestrarioTela . ' | Composicion: ' . $composicionTela . ' | Peso: ' . $pesoTela . ' | Rango: ' . $rangoTela . ' | Pagina: ' . $paginaTela;
												$textoTelaMostrar = 'Tela ' . $articuloTela . ' | Muestrario: ' . $muestrarioTela . ' | Composicion: ' . $composicionTela;
												$fotoValor = trim((string) ($filaTela['foto'] ?? ''));
												$rutaFoto = '';

												if ($fotoValor !== '') {
													$candidatos = [];
													if (pathinfo($fotoValor, PATHINFO_EXTENSION) !== '') {
														$candidatos[] = 'fotos/' . $fotoValor;
													} else {
														$candidatos[] = 'fotos/' . $fotoValor . '.jpeg';
														$candidatos[] = 'fotos/' . $fotoValor . '.jpg';
														$candidatos[] = 'fotos/' . $fotoValor . '.png';
													}

													foreach ($candidatos as $candidato) {
														if (file_exists(__DIR__ . '/' . $candidato)) {
															$rutaFoto = $candidato;
															break;
														}
													}
												}
											?>
											<tr>
												<td><?php echo htmlspecialchars($articuloTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($muestrarioTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($composicionTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($pesoTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($rangoTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars($paginaTela, ENT_QUOTES, 'UTF-8'); ?></td>
												<td>
													<?php if ($rutaFoto !== ''): ?>
														<img class="mini-foto-tela" src="<?php echo htmlspecialchars($rutaFoto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de tela">
													<?php endif; ?>
												</td>
												<td class="cell-check">
													<?php $estaSeleccionado = producto_marcado_en_pedido($textoTela, $productosSeleccionadosMapa, $productosPedidoActual); ?>
													<input
														type="checkbox"
														class="check-producto"
														data-tipo="tela"
														data-articulo="<?php echo htmlspecialchars($articuloTela, ENT_QUOTES, 'UTF-8'); ?>"
														data-rango="<?php echo htmlspecialchars($rangoTela, ENT_QUOTES, 'UTF-8'); ?>"
																data-item="<?php echo htmlspecialchars($textoTelaMostrar, ENT_QUOTES, 'UTF-8'); ?>"
														data-producto="<?php echo htmlspecialchars($textoTela, ENT_QUOTES, 'UTF-8'); ?>"
														data-precio="0"
														<?php echo $estaSeleccionado ? 'checked' : ''; ?>
													>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</section>
					<?php endforeach; ?>
				</div>
			<?php else: ?>
				<p class="sin-datos">No hay registros en la tabla telas.</p>
			<?php endif; ?>
		</section>

		<h2 class="titulo-seccion-productos">Daywear</h2>

		<?php foreach ($productosObjetivo as $producto): ?>
			<section class="bloque-tabla">
				<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
					<span><?php echo htmlspecialchars(limpiar_traduccion_titulo($producto['titulo']), ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="acordeon-flecha">▼</span>
				</div>
				<?php if (!empty($datosPorProducto[$producto['db']])): ?>
					<div class="tabla-wrapper">
						<table>
							<thead>
								<tr>
									<th rowspan="2">Price range</th>
									<th rowspan="2">Articulo</th>
									<th>Single Breasted</th>
									<th>Double Breasted</th>
									<th>Special</th>
								</tr>
								<tr>
									<th>SRP</th>
									<th>SRP</th>
									<th>SRP</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($datosPorProducto[$producto['db']] as $fila): ?>
									<?php
										$rangoProducto = (int) $fila['rango'];
										$grupoFilaProducto = 'ropadia-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($producto['db'])) . '-' . $rangoProducto;
										$precioUnBoton = (int) $fila['unboton'];
										$precioDosBotones = (int) $fila['dosbotones'];
										$precioEspecial = (int) $fila['especial'];
										$precioUnBotonMostrar = formatear_precio_mostrar($precioUnBoton);
										$precioDosBotonesMostrar = formatear_precio_mostrar($precioDosBotones);
										$precioEspecialMostrar = formatear_precio_mostrar($precioEspecial);
										$prefijoProducto = $producto['titulo'] . ' | Rango: ' . $rangoProducto;
										$textoProductoUnBoton = $prefijoProducto . ' | Tipo: Un botón | Precio: ' . $precioUnBoton;
										$textoProductoDosBotones = $prefijoProducto . ' | Tipo: Dos botones | Precio: ' . $precioDosBotones;
										$textoProductoEspecial = $prefijoProducto . ' | Tipo: Especial | Precio: ' . $precioEspecial;
										$textoProductoUnBotonMostrar = $prefijoProducto . ' | Tipo: Un botón | Precio: ' . $precioUnBotonMostrar;
										$textoProductoDosBotonesMostrar = $prefijoProducto . ' | Tipo: Dos botones | Precio: ' . $precioDosBotonesMostrar;
										$textoProductoEspecialMostrar = $prefijoProducto . ' | Tipo: Especial | Precio: ' . $precioEspecialMostrar;
										$seleccionadoUnBoton = producto_marcado_en_pedido($textoProductoUnBoton, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoProducto . ' | Un botón: ', $productosPedidoActual);
										$seleccionadoDosBotones = producto_marcado_en_pedido($textoProductoDosBotones, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoProducto . ' | Dos botones: ', $productosPedidoActual);
										$seleccionadoEspecial = producto_marcado_en_pedido($textoProductoEspecial, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoProducto . ' | Especial: ', $productosPedidoActual);
									?>
									<tr class="fila-producto-rango" data-rango="<?php echo $rangoProducto; ?>">
										<td><?php echo $rangoProducto; ?></td>
										<td class="articulos-rango" data-articulos-disponibles="<?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) $rangoProducto] ?? []), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) $rangoProducto] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
										<td class="cell-check">
											<label class="precio-opcion">
												<span><?php echo $precioUnBotonMostrar; ?></span>
											<input
												type="checkbox"
												class="check-producto"
												data-tipo="producto"
												data-rango="<?php echo $rangoProducto; ?>"
														data-articulos="<?php echo htmlspecialchars(implode(',', $articulosTelasPorRango[(string) $rangoProducto] ?? []), ENT_QUOTES, 'UTF-8'); ?>"
												data-grupo-fila="<?php echo htmlspecialchars($grupoFilaProducto, ENT_QUOTES, 'UTF-8'); ?>"
												data-item="<?php echo htmlspecialchars($textoProductoUnBotonMostrar, ENT_QUOTES, 'UTF-8'); ?>"
												data-producto="<?php echo htmlspecialchars($textoProductoUnBoton, ENT_QUOTES, 'UTF-8'); ?>"
												data-precio="<?php echo $precioUnBoton; ?>"
												<?php echo $seleccionadoUnBoton ? 'checked' : ''; ?>
											>
											</label>
										</td>
										<td class="cell-check">
											<label class="precio-opcion">
												<span><?php echo $precioDosBotonesMostrar; ?></span>
											<input
												type="checkbox"
												class="check-producto"
												data-tipo="producto"
												data-rango="<?php echo $rangoProducto; ?>"
												data-grupo-fila="<?php echo htmlspecialchars($grupoFilaProducto, ENT_QUOTES, 'UTF-8'); ?>"
												data-item="<?php echo htmlspecialchars($textoProductoDosBotonesMostrar, ENT_QUOTES, 'UTF-8'); ?>"
												data-producto="<?php echo htmlspecialchars($textoProductoDosBotones, ENT_QUOTES, 'UTF-8'); ?>"
												data-precio="<?php echo $precioDosBotones; ?>"
												<?php echo $seleccionadoDosBotones ? 'checked' : ''; ?>
											>
											</label>
										</td>
										<td class="cell-check">
											<label class="precio-opcion">
												<span><?php echo $precioEspecialMostrar; ?></span>
											<input
												type="checkbox"
												class="check-producto"
												data-tipo="producto"
												data-rango="<?php echo $rangoProducto; ?>"
												data-grupo-fila="<?php echo htmlspecialchars($grupoFilaProducto, ENT_QUOTES, 'UTF-8'); ?>"
												data-item="<?php echo htmlspecialchars($textoProductoEspecialMostrar, ENT_QUOTES, 'UTF-8'); ?>"
												data-producto="<?php echo htmlspecialchars($textoProductoEspecial, ENT_QUOTES, 'UTF-8'); ?>"
												data-precio="<?php echo $precioEspecial; ?>"
												<?php echo $seleccionadoEspecial ? 'checked' : ''; ?>
											>
											</label>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<p class="sin-datos">No hay registros para este producto.</p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>

		<?php foreach ($productosUnPrecio as $productoUnPrecio): ?>
			<?php if ($productoUnPrecio['db'] === 'esmoquin'): ?>
				<h3>Eveningwear (ropa de noche)</h3>
			<?php endif; ?>
			<section class="bloque-tabla">
				<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
					<span><?php echo htmlspecialchars(limpiar_traduccion_titulo($productoUnPrecio['titulo']), ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="acordeon-flecha">▼</span>
				</div>
				<?php if (!empty($datosUnPrecio[$productoUnPrecio['db']])): ?>
					<div class="tabla-wrapper">
						<table>
							<thead>
								<tr>
									<th>Price range</th>
										<th>Articulo</th>
									<th>SRP</th>
									<th class="col-seleccion">Agregar</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($datosUnPrecio[$productoUnPrecio['db']] as $filaUnPrecio): ?>
									<?php
										$precioUnico = (int) $filaUnPrecio['precio'];
										$precioUnicoMostrar = formatear_precio_mostrar($precioUnico);
										$textoProductoUnPrecio = $productoUnPrecio['titulo'] . ' | Rango: ' . (int) $filaUnPrecio['rango'] . ' | Precio: ' . $precioUnico;
										$textoProductoUnPrecioMostrar = $productoUnPrecio['titulo'] . ' | Rango: ' . (int) $filaUnPrecio['rango'] . ' | Precio: ' . $precioUnicoMostrar;
									?>
									<tr class="fila-producto-rango" data-rango="<?php echo (int) $filaUnPrecio['rango']; ?>">
										<td><?php echo (int) $filaUnPrecio['rango']; ?></td>
										<td class="articulos-rango" data-articulos-disponibles="<?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) (int) $filaUnPrecio['rango']] ?? []), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) (int) $filaUnPrecio['rango']] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
										<td><?php echo $precioUnicoMostrar; ?></td>
										<td class="cell-check">
											<?php $estaSeleccionado = producto_marcado_en_pedido($textoProductoUnPrecio, $productosSeleccionadosMapa, $productosPedidoActual); ?>
											<input
												type="checkbox"
												class="check-producto"
												data-tipo="producto"
												data-rango="<?php echo (int) $filaUnPrecio['rango']; ?>"
														data-articulos="<?php echo htmlspecialchars(implode(',', $articulosTelasPorRango[(string) (int) $filaUnPrecio['rango']] ?? []), ENT_QUOTES, 'UTF-8'); ?>"
												data-item="<?php echo htmlspecialchars($textoProductoUnPrecioMostrar, ENT_QUOTES, 'UTF-8'); ?>"
												data-producto="<?php echo htmlspecialchars($textoProductoUnPrecio, ENT_QUOTES, 'UTF-8'); ?>"
												data-precio="<?php echo $precioUnico; ?>"
												<?php echo $estaSeleccionado ? 'checked' : ''; ?>
											>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else: ?>
					<p class="sin-datos">No hay registros para este producto.</p>
				<?php endif; ?>
			</section>

			<?php if ($productoUnPrecio['db'] === 'chaleco'): ?>
				<section class="bloque-tabla">
					<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
						<span>Overcoat</span>
						<span class="acordeon-flecha">▼</span>
					</div>
					<?php if (!empty($datosSobretodo)): ?>
						<div class="tabla-wrapper">
							<table>
								<thead>
									<tr>
										<th>Rango</th>
										<th>Articulo</th>
										<th>Categoria 1</th>
										<th>Categoria 2</th>
										<th>Categoria 3</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($datosSobretodo as $filaSobretodo): ?>
										<?php
											$rangoSobretodo = (int) $filaSobretodo['rango'];
											$grupoFilaSobretodo = 'sobretodo-' . $rangoSobretodo;
											$precioCategoria1 = (int) $filaSobretodo['categoria1'];
											$precioCategoria2 = (int) $filaSobretodo['categoria2'];
											$precioCategoria3 = (int) $filaSobretodo['categoria3'];
											$precioCategoria1Mostrar = formatear_precio_mostrar($precioCategoria1);
											$precioCategoria2Mostrar = formatear_precio_mostrar($precioCategoria2);
											$precioCategoria3Mostrar = formatear_precio_mostrar($precioCategoria3);
											$prefijoSobretodo = 'Overcoat (Sobretodos) | Rango: ' . $rangoSobretodo;
											$textoSobretodoCategoria1 = $prefijoSobretodo . ' | Tipo: Categoria 1 | Precio: ' . $precioCategoria1;
											$textoSobretodoCategoria2 = $prefijoSobretodo . ' | Tipo: Categoria 2 | Precio: ' . $precioCategoria2;
											$textoSobretodoCategoria3 = $prefijoSobretodo . ' | Tipo: Categoria 3 | Precio: ' . $precioCategoria3;
											$textoSobretodoCategoria1Mostrar = $prefijoSobretodo . ' | Tipo: Categoria 1 | Precio: ' . $precioCategoria1Mostrar;
											$textoSobretodoCategoria2Mostrar = $prefijoSobretodo . ' | Tipo: Categoria 2 | Precio: ' . $precioCategoria2Mostrar;
											$textoSobretodoCategoria3Mostrar = $prefijoSobretodo . ' | Tipo: Categoria 3 | Precio: ' . $precioCategoria3Mostrar;
											$seleccionadoCategoria1 = producto_marcado_en_pedido($textoSobretodoCategoria1, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoSobretodo . ' | Categoria 1: ', $productosPedidoActual);
											$seleccionadoCategoria2 = producto_marcado_en_pedido($textoSobretodoCategoria2, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoSobretodo . ' | Categoria 2: ', $productosPedidoActual);
											$seleccionadoCategoria3 = producto_marcado_en_pedido($textoSobretodoCategoria3, $productosSeleccionadosMapa, $productosPedidoActual) || producto_marcado_por_prefijo($prefijoSobretodo . ' | Categoria 3: ', $productosPedidoActual);
										?>
										<tr class="fila-producto-rango" data-rango="<?php echo $rangoSobretodo; ?>">
											<td><?php echo $rangoSobretodo; ?></td>
											<td class="articulos-rango" data-articulos-disponibles="<?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) $rangoSobretodo] ?? []), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(implode(', ', $articulosTelasPorRango[(string) $rangoSobretodo] ?? []), ENT_QUOTES, 'UTF-8'); ?></td>
											<td class="cell-check">
												<label class="precio-opcion">
													<span><?php echo $precioCategoria1Mostrar; ?></span>
												<input
													type="checkbox"
													class="check-producto"
														data-tipo="producto"
														data-rango="<?php echo $rangoSobretodo; ?>"
															data-articulos="<?php echo htmlspecialchars(implode(',', $articulosTelasPorRango[(string) $rangoSobretodo] ?? []), ENT_QUOTES, 'UTF-8'); ?>"
													data-grupo-fila="<?php echo htmlspecialchars($grupoFilaSobretodo, ENT_QUOTES, 'UTF-8'); ?>"
													data-item="<?php echo htmlspecialchars($textoSobretodoCategoria1Mostrar, ENT_QUOTES, 'UTF-8'); ?>"
													data-producto="<?php echo htmlspecialchars($textoSobretodoCategoria1, ENT_QUOTES, 'UTF-8'); ?>"
													data-precio="<?php echo $precioCategoria1; ?>"
													<?php echo $seleccionadoCategoria1 ? 'checked' : ''; ?>
												>
												</label>
											</td>
											<td class="cell-check">
												<label class="precio-opcion">
													<span><?php echo $precioCategoria2Mostrar; ?></span>
												<input
													type="checkbox"
													class="check-producto"
														data-tipo="producto"
														data-rango="<?php echo $rangoSobretodo; ?>"
													data-grupo-fila="<?php echo htmlspecialchars($grupoFilaSobretodo, ENT_QUOTES, 'UTF-8'); ?>"
													data-item="<?php echo htmlspecialchars($textoSobretodoCategoria2Mostrar, ENT_QUOTES, 'UTF-8'); ?>"
													data-producto="<?php echo htmlspecialchars($textoSobretodoCategoria2, ENT_QUOTES, 'UTF-8'); ?>"
													data-precio="<?php echo $precioCategoria2; ?>"
													<?php echo $seleccionadoCategoria2 ? 'checked' : ''; ?>
												>
												</label>
											</td>
											<td class="cell-check">
												<label class="precio-opcion">
													<span><?php echo $precioCategoria3Mostrar; ?></span>
												<input
													type="checkbox"
													class="check-producto"
														data-tipo="producto"
														data-rango="<?php echo $rangoSobretodo; ?>"
													data-grupo-fila="<?php echo htmlspecialchars($grupoFilaSobretodo, ENT_QUOTES, 'UTF-8'); ?>"
													data-item="<?php echo htmlspecialchars($textoSobretodoCategoria3Mostrar, ENT_QUOTES, 'UTF-8'); ?>"
													data-producto="<?php echo htmlspecialchars($textoSobretodoCategoria3, ENT_QUOTES, 'UTF-8'); ?>"
													data-precio="<?php echo $precioCategoria3; ?>"
													<?php echo $seleccionadoCategoria3 ? 'checked' : ''; ?>
												>
												</label>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<p class="sin-datos">No hay registros en la tabla sobretodo.</p>
					<?php endif; ?>
				</section>

				<section class="bloque-tabla">
					<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
						<span>Accessories</span>
						<span class="acordeon-flecha">▼</span>
					</div>
					<?php if ($errorAccesorios !== ''): ?>
						<p class="sin-datos"><?php echo htmlspecialchars($errorAccesorios, ENT_QUOTES, 'UTF-8'); ?></p>
					<?php elseif (!empty($columnasAccesorios)): ?>
						<div class="tabla-wrapper">
							<table>
								<thead>
									<tr>
										<?php foreach ($columnasAccesorios as $indiceColumnaAccesorio => $columnaAccesorio): ?>
											<?php if (strtolower($columnaAccesorio) === 'id') continue; ?>
											<th><?php echo $indiceColumnaAccesorio === 1 ? '' : ($indiceColumnaAccesorio === 2 ? 'SRP' : htmlspecialchars($columnaAccesorio, ENT_QUOTES, 'UTF-8')); ?></th>
										<?php endforeach; ?>
										<th class="col-seleccion">Agregar</th>
									</tr>
								</thead>
								<tbody>
									<?php if (!empty($datosAccesorios)): ?>
										<?php foreach ($datosAccesorios as $indiceAccesorio => $filaAccesorio): ?>
											<?php
												$partesDescripcionAccesorio = [];
												foreach ($columnasAccesorios as $columnaAccesorio) {
													if (strtolower($columnaAccesorio) === 'id') {
														continue;
													}

													$valorColumnaAccesorio = trim((string) ($filaAccesorio[$columnaAccesorio] ?? ''));
													if ($valorColumnaAccesorio !== '') {
														$partesDescripcionAccesorio[] = $columnaAccesorio . ': ' . $valorColumnaAccesorio;
													}
												}

												if (empty($partesDescripcionAccesorio)) {
													$partesDescripcionAccesorio[] = 'Fila: ' . ($indiceAccesorio + 1);
												}

												$textoAccesorio = 'Accessories | ' . implode(' | ', $partesDescripcionAccesorio);
												$precioAccesorio = 0;
												$columnasPrecioAccesorio = ['precio', 'price', 'valor', 'monto', 'costo'];
												foreach ($columnasPrecioAccesorio as $columnaPrecioAccesorio) {
													foreach ($filaAccesorio as $nombreColumnaAccesorio => $valorColumnaAccesorio) {
														if (strtolower((string) $nombreColumnaAccesorio) === $columnaPrecioAccesorio && is_numeric($valorColumnaAccesorio)) {
															$precioAccesorio = (int) round((float) $valorColumnaAccesorio);
															break 2;
														}
													}
												}
											?>
											<tr>
												<?php foreach ($columnasAccesorios as $columnaAccesorio): ?>
													<?php if (strtolower($columnaAccesorio) === 'id') continue; ?>
													<td><?php echo htmlspecialchars((string) ($filaAccesorio[$columnaAccesorio] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
												<?php endforeach; ?>
												<td class="cell-check">
													<?php $estaSeleccionado = producto_marcado_en_pedido($textoAccesorio, $productosSeleccionadosMapa, $productosPedidoActual); ?>
													<input
														type="checkbox"
														class="check-producto"
														data-tipo="producto"
														data-item="<?php echo htmlspecialchars($textoAccesorio, ENT_QUOTES, 'UTF-8'); ?>"
														data-producto="<?php echo htmlspecialchars($textoAccesorio, ENT_QUOTES, 'UTF-8'); ?>"
														data-precio="<?php echo $precioAccesorio; ?>"
														<?php echo $estaSeleccionado ? 'checked' : ''; ?>
													>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php else: ?>
										<tr>
											<td colspan="<?php echo count($columnasAccesorios); ?>">No hay registros en la tabla accesorios.</td>
										</tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<p class="sin-datos">No se encontraron columnas en la tabla accesorios.</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		<?php endforeach; ?>

	</main>

	<div id="modalImagenTela" class="modal-imagen" aria-hidden="true">
		<div class="modal-imagen-contenido">
			<button type="button" id="cerrarModalImagenTela" class="modal-imagen-cerrar" aria-label="Cerrar imagen ampliada">×</button>
			<img id="imagenTelaAmpliada" class="modal-imagen-foto" src="" alt="Imagen ampliada de tela">
		</div>
	</div>

	<script>
		var listaPedido = document.getElementById('listaPedido');
		var mensajeListaVacia = document.getElementById('mensajeListaVacia');
		var itemsSeleccionadosInput = document.getElementById('itemsSeleccionadosInput');
		var botonGuardarPedido = document.getElementById('botonGuardarPedido');
		var modoEdicionPedido = document.getElementById('modoEdicionPedido');
		var totalPedidoValor = document.getElementById('totalPedidoValor');
		var modalImagenTela = document.getElementById('modalImagenTela');
		var imagenTelaAmpliada = document.getElementById('imagenTelaAmpliada');
		var cerrarModalImagenTela = document.getElementById('cerrarModalImagenTela');
		var contadorOrdenSeleccion = 0;
		var itemsEnPedido = [];

		var formatearPrecioMostrar = function (valor) {
			return new Intl.NumberFormat('es-ES', {
				maximumFractionDigits: 0,
				minimumFractionDigits: 0
			}).format(valor);
		};

		var actualizarListaPedido = function () {
			if (!listaPedido) {
				return;
			}

			listaPedido.innerHTML = '';
			var itemsGuardar = [];
			var totalPedido = 0;
			var gruposPorArticulo = {};
			var ordenArticulos = [];
			var itemsSinArticulo = [];

			var itemsOrdenados = itemsEnPedido.slice().sort(function (a, b) {
				return (a.orden || 0) - (b.orden || 0);
			});

			itemsOrdenados.forEach(function (item) {
				var articulo = item.articulo || '';
				if (articulo === '' || articulo === 'Sin articulo') {
					itemsSinArticulo.push(item);
					return;
				}

				if (!gruposPorArticulo[articulo]) {
					gruposPorArticulo[articulo] = {
						rango: item.rango || '',
						telas: [],
						productos: []
					};
					ordenArticulos.push(articulo);
				}

				if (item.tipo === 'tela') {
					if (item.rango) {
						gruposPorArticulo[articulo].rango = item.rango;
					}
					gruposPorArticulo[articulo].telas.push(item);
				} else {
					gruposPorArticulo[articulo].productos.push(item);
				}
			});

			ordenArticulos.forEach(function (articuloGrupo) {
				var grupo = gruposPorArticulo[articuloGrupo];
				var itemsGrupo = grupo.telas.concat(grupo.productos);

				var encabezadoArticulo = document.createElement('li');
				encabezadoArticulo.className = 'encabezado-rango';

				var textoHeader = document.createElement('span');
				textoHeader.textContent = articuloGrupo;
				if (grupo.rango !== '') {
					var rangoTela = document.createElement('span');
					rangoTela.className = 'rango-tela';
					rangoTela.textContent = ' - Rango: ' + grupo.rango;
					textoHeader.appendChild(rangoTela);
				}
				encabezadoArticulo.appendChild(textoHeader);

				var btnQuitarArticulo = document.createElement('button');
				btnQuitarArticulo.type = 'button';
				btnQuitarArticulo.className = 'btn-quitar-item';
				btnQuitarArticulo.innerHTML = '&times;';
				btnQuitarArticulo.title = 'Quitar tela y sus productos del pedido';
				btnQuitarArticulo.addEventListener('click', function () {
					removerArticuloCompleto(articuloGrupo);
				});
				encabezadoArticulo.appendChild(btnQuitarArticulo);

				listaPedido.appendChild(encabezadoArticulo);

				itemsGrupo.forEach(function (item) {
					var li = document.createElement('li');
					li.className = 'item-pedido';

					var contenidoSpan = document.createElement('span');
					var partesTexto = item.itemTexto.split(' | Precio: ');
					if (partesTexto.length > 1) {
						var textoProducto = partesTexto.shift().replace(/ \| Rango: [^|]+/, '');
						contenidoSpan.appendChild(document.createTextNode(textoProducto));
						var precioNegrita = document.createElement('strong');
						precioNegrita.className = 'precio-item';
						precioNegrita.textContent = ' | ' + partesTexto.join(' | Precio: ');
						contenidoSpan.appendChild(precioNegrita);
					} else {
						contenidoSpan.textContent = item.itemTexto;
					}
					li.appendChild(contenidoSpan);

					var btnQuitar = document.createElement('button');
					btnQuitar.type = 'button';
					btnQuitar.className = 'btn-quitar-item';
					btnQuitar.innerHTML = '&times;';
					btnQuitar.title = 'Quitar del pedido';
					btnQuitar.addEventListener('click', function () {
						removerItemPorProducto(item.producto);
					});
					li.appendChild(btnQuitar);

					listaPedido.appendChild(li);

					if (item.producto !== '') {
						itemsGuardar.push({
							producto: item.producto,
							precio: item.precio
						});
					}

					totalPedido += item.precio;
				});
			});

			if (itemsSinArticulo.length > 0) {
				var encabezadoOtros = document.createElement('li');
				encabezadoOtros.className = 'encabezado-rango';
				encabezadoOtros.textContent = 'Otros / Accesorios';
				listaPedido.appendChild(encabezadoOtros);

				itemsSinArticulo.forEach(function (item) {
					var li = document.createElement('li');
					li.className = 'item-pedido';

					var contenidoSpan = document.createElement('span');
					var partesTexto = item.itemTexto.split(' | Precio: ');
					if (partesTexto.length > 1) {
						var textoProducto = partesTexto.shift().replace(/ \| Rango: [^|]+/, '');
						contenidoSpan.appendChild(document.createTextNode(textoProducto));
						var precioNegrita = document.createElement('strong');
						precioNegrita.className = 'precio-item';
						precioNegrita.textContent = ' | ' + partesTexto.join(' | Precio: ');
						contenidoSpan.appendChild(precioNegrita);
					} else {
						contenidoSpan.textContent = item.itemTexto;
					}
					li.appendChild(contenidoSpan);

					var btnQuitar = document.createElement('button');
					btnQuitar.type = 'button';
					btnQuitar.className = 'btn-quitar-item';
					btnQuitar.innerHTML = '&times;';
					btnQuitar.title = 'Quitar del pedido';
					btnQuitar.addEventListener('click', function () {
						removerItemPorProducto(item.producto);
					});
					li.appendChild(btnQuitar);

					listaPedido.appendChild(li);

					if (item.producto !== '') {
						itemsGuardar.push({
							producto: item.producto,
							precio: item.precio
						});
					}

					totalPedido += item.precio;
				});
			}

			if (totalPedidoValor) {
				totalPedidoValor.textContent = formatearPrecioMostrar(Math.round(totalPedido));
			}

			if (itemsSeleccionadosInput) {
				itemsSeleccionadosInput.value = JSON.stringify(itemsGuardar);
			}

			if (botonGuardarPedido) {
				var permiteGuardarVacio = modoEdicionPedido && modoEdicionPedido.value === '1';
				botonGuardarPedido.disabled = itemsGuardar.length === 0 && !permiteGuardarVacio;
			}

			if (mensajeListaVacia) {
				mensajeListaVacia.style.display = itemsEnPedido.length > 0 ? 'none' : 'block';
			}
		};

		var removerItemPorProducto = function (productoKey) {
			var itemEncontrado = null;
			for (var i = 0; i < itemsEnPedido.length; i++) {
				if (itemsEnPedido[i].producto === productoKey) {
					itemEncontrado = itemsEnPedido[i];
					break;
				}
			}

			if (!itemEncontrado) {
				return;
			}

			if (itemEncontrado.tipo === 'tela') {
				removerArticuloCompleto(itemEncontrado.articulo);
				return;
			}

			itemsEnPedido = itemsEnPedido.filter(function (it) {
				return it.producto !== productoKey;
			});

			document.querySelectorAll('.check-producto[data-producto="' + productoKey.replace(/"/g, '\\"') + '"]').forEach(function (chk) {
				chk.checked = false;
				chk.removeAttribute('data-orden-seleccion');
			});

			actualizarListaPedido();
		};

		var removerArticuloCompleto = function (articulo) {
			itemsEnPedido = itemsEnPedido.filter(function (it) {
				return it.articulo !== articulo;
			});

			document.querySelectorAll('.check-producto[data-articulo="' + articulo.replace(/"/g, '\\"') + '"]').forEach(function (chk) {
				chk.checked = false;
				chk.removeAttribute('data-orden-seleccion');
			});

			actualizarProductosPorRango();
			actualizarListaPedido();
		};

		document.querySelectorAll('.bloque-tabla').forEach(function (bloque) {
			var titulo = bloque.querySelector('.acordeon-linea');
			if (!titulo) {
				return;
			}

			var alternar = function () {
				var estaAbierta = bloque.classList.toggle('abierta');
				titulo.setAttribute('aria-expanded', estaAbierta ? 'true' : 'false');
			};

			titulo.addEventListener('click', alternar);
			titulo.addEventListener('keydown', function (evento) {
				if (evento.key === 'Enter' || evento.key === ' ') {
					evento.preventDefault();
					alternar();
				}
			});
		});

		var actualizarProductosPorRango = function () {
			var telaActiva = document.querySelector('.check-producto[data-tipo="tela"]:checked');

			document.querySelectorAll('.fila-producto-rango-generada').forEach(function (filaGenerada) {
				filaGenerada.remove();
			});

			if (!telaActiva) {
				document.querySelectorAll('.fila-producto-rango:not(.fila-producto-rango-generada)').forEach(function (fila) {
					fila.style.display = '';
					fila.querySelectorAll('.check-producto[data-tipo="producto"]').forEach(function (producto) {
						producto.style.display = 'none';
						producto.disabled = true;
						producto.checked = false;
					});
				});
				return;
			}

			var rangoTela = (telaActiva.getAttribute('data-rango') || '').trim();
			var articuloTela = (telaActiva.getAttribute('data-articulo') || '').trim();

			document.querySelectorAll('.fila-producto-rango:not(.fila-producto-rango-generada)').forEach(function (fila) {
				var rangoProducto = (fila.getAttribute('data-rango') || '').trim();

				if (rangoProducto !== rangoTela) {
					fila.style.display = 'none';
					fila.querySelectorAll('.check-producto[data-tipo="producto"]').forEach(function (producto) {
						producto.disabled = true;
					});
					return;
				}

				fila.style.display = 'none';
				fila.querySelectorAll('.check-producto[data-tipo="producto"]').forEach(function (producto) {
					producto.disabled = true;
				});

				var filaProducto = fila.cloneNode(true);
				filaProducto.classList.add('fila-producto-rango-generada');
				filaProducto.style.display = '';

				var celdaArticulo = filaProducto.querySelector('.articulos-rango');
				if (celdaArticulo) {
					celdaArticulo.textContent = articuloTela;
				}

				filaProducto.querySelectorAll('.check-producto[data-tipo="producto"]').forEach(function (producto) {
					delete producto.dataset.eventoCambioRegistrado;
					producto.removeAttribute('data-evento-cambio-registrado');

					var productoOriginal = producto.getAttribute('data-producto-base') || producto.getAttribute('data-producto') || '';
					var itemOriginal = producto.getAttribute('data-item-base') || producto.getAttribute('data-item') || '';
					var grupoFilaOriginal = producto.getAttribute('data-grupo-fila-base') || producto.getAttribute('data-grupo-fila') || '';
					var productoConArticulo = productoOriginal + ' | Articulo: ' + articuloTela;

					var estaEnPedido = itemsEnPedido.some(function (it) {
						return it.producto === productoConArticulo;
					});

					producto.setAttribute('data-producto-base', productoOriginal);
					producto.setAttribute('data-item-base', itemOriginal);
					producto.setAttribute('data-articulo', articuloTela);
					producto.setAttribute('data-producto', productoConArticulo);

					if (grupoFilaOriginal !== '') {
						producto.setAttribute('data-grupo-fila-base', grupoFilaOriginal);
						producto.setAttribute('data-grupo-fila', grupoFilaOriginal + '-' + articuloTela.replace(/[^a-z0-9_-]/gi, '_'));
					}

					producto.checked = estaEnPedido;
					producto.style.display = '';
					producto.disabled = false;
					registrarEventoCambioCheckbox(producto);
				});

				fila.insertAdjacentElement('afterend', filaProducto);
			});
		};

		var procesarCambioCheckbox = function (check) {
			var tipo = check.getAttribute('data-tipo') || 'producto';
			var articulo = check.getAttribute('data-articulo') || '';
			var rango = check.getAttribute('data-rango') || '';
			var itemTexto = check.getAttribute('data-item') || '';
			var producto = check.getAttribute('data-producto') || '';
			var precio = parseFloat(check.getAttribute('data-precio') || '0');
			var precioValido = isNaN(precio) ? 0 : precio;
			var grupoFila = check.getAttribute('data-grupo-fila') || '';
			var grupoFilaBase = check.getAttribute('data-grupo-fila-base') || grupoFila;

			if (tipo === 'tela') {
				if (check.checked) {
					// Desmarcar cualquier otra tela en la interfaz para que solo aparezca una tela seleccionada a la vez
					document.querySelectorAll('.check-producto[data-tipo="tela"]').forEach(function (otraTela) {
						if (otraTela !== check) {
							otraTela.checked = false;
						}
					});

					// Asegurar que esta tela esté en itemsEnPedido
					var yaExisteTela = itemsEnPedido.some(function (it) {
						return it.producto === producto;
					});
					if (!yaExisteTela) {
						contadorOrdenSeleccion += 1;
						itemsEnPedido.push({
							tipo: 'tela',
							articulo: articulo,
							rango: rango,
							itemTexto: itemTexto,
							producto: producto,
							precio: 0,
							orden: contadorOrdenSeleccion
						});
					}

					actualizarProductosPorRango();
					actualizarListaPedido();
				} else {
					// Si el usuario desmarca la tela activa actual, se remueve del pedido
					removerArticuloCompleto(articulo);
				}
				return;
			}

			// Es un producto o accesorio
			if (check.checked) {
				contadorOrdenSeleccion += 1;

				if (grupoFila !== '') {
					document.querySelectorAll('.check-producto[data-grupo-fila="' + grupoFila.replace(/"/g, '\\"') + '"]').forEach(function (checkRelacionado) {
						if (checkRelacionado !== check) {
							checkRelacionado.checked = false;
							var relProd = checkRelacionado.getAttribute('data-producto') || '';
							itemsEnPedido = itemsEnPedido.filter(function (it) {
								return it.producto !== relProd;
							});
						}
					});
				}

				if (grupoFilaBase !== '' && articulo !== '') {
					itemsEnPedido = itemsEnPedido.filter(function (it) {
						return !(it.grupoFilaBase === grupoFilaBase && it.articulo === articulo);
					});
				}

				// Asegurar que la tela correspondiente esté en itemsEnPedido si es un producto con artículo
				if (articulo !== '' && articulo !== 'Sin articulo') {
					var telaCheck = document.querySelector('.check-producto[data-tipo="tela"][data-articulo="' + articulo.replace(/"/g, '\\"') + '"]');
					if (telaCheck) {
						var telaProd = telaCheck.getAttribute('data-producto') || '';
						var tieneTelaEnPedido = itemsEnPedido.some(function (it) {
							return it.producto === telaProd;
						});
						if (!tieneTelaEnPedido) {
							contadorOrdenSeleccion += 1;
							itemsEnPedido.push({
								tipo: 'tela',
								articulo: articulo,
								rango: telaCheck.getAttribute('data-rango') || rango,
								itemTexto: telaCheck.getAttribute('data-item') || ('Tela ' + articulo),
								producto: telaProd,
								precio: 0,
								orden: contadorOrdenSeleccion
							});
						}
					}
				}

				itemsEnPedido.push({
					tipo: 'producto',
					articulo: articulo,
					rango: rango,
					itemTexto: itemTexto,
					producto: producto,
					precio: precioValido,
					grupoFilaBase: grupoFilaBase,
					orden: contadorOrdenSeleccion
				});

				actualizarListaPedido();
			} else {
				// Producto desmarcado
				itemsEnPedido = itemsEnPedido.filter(function (it) {
					return it.producto !== producto;
				});
				actualizarListaPedido();
			}
		};

		var registrarEventoCambioCheckbox = function (check) {
			if (!check) {
				return;
			}
			if (check.dataset.eventoCambioRegistrado !== '1') {
				check.addEventListener('change', function () {
					procesarCambioCheckbox(check);
				});
				check.dataset.eventoCambioRegistrado = '1';
			}
		};

		document.querySelectorAll('.check-producto').forEach(function (check) {
			registrarEventoCambioCheckbox(check);
		});

		var cerrarVisorImagen = function () {
			if (!modalImagenTela || !imagenTelaAmpliada) {
				return;
			}

			modalImagenTela.classList.remove('abierto');
			modalImagenTela.setAttribute('aria-hidden', 'true');
			imagenTelaAmpliada.setAttribute('src', '');
		};

		document.querySelectorAll('.mini-foto-tela').forEach(function (imagen) {
			imagen.addEventListener('click', function () {
				if (!modalImagenTela || !imagenTelaAmpliada) {
					return;
				}

				var rutaImagen = imagen.getAttribute('src') || '';
				var textoAlternativo = imagen.getAttribute('alt') || 'Imagen ampliada de tela';

				if (rutaImagen === '') {
					return;
				}

				imagenTelaAmpliada.setAttribute('src', rutaImagen);
				imagenTelaAmpliada.setAttribute('alt', textoAlternativo);
				modalImagenTela.classList.add('abierto');
				modalImagenTela.setAttribute('aria-hidden', 'false');
			});
		});

		if (cerrarModalImagenTela) {
			cerrarModalImagenTela.addEventListener('click', cerrarVisorImagen);
		}

		if (modalImagenTela) {
			modalImagenTela.addEventListener('click', function (evento) {
				if (evento.target === modalImagenTela) {
					cerrarVisorImagen();
				}
			});
		}

		document.addEventListener('keydown', function (evento) {
			if (evento.key === 'Escape' && modalImagenTela && modalImagenTela.classList.contains('abierto')) {
				cerrarVisorImagen();
			}
		});

		var inicializarItemsDesdeDOM = function () {
			var telasIniciales = [];
			document.querySelectorAll('.check-producto[data-tipo="tela"]:checked').forEach(function (telaCheck) {
				contadorOrdenSeleccion += 1;
				var articulo = telaCheck.getAttribute('data-articulo') || '';
				var rango = telaCheck.getAttribute('data-rango') || '';
				var itemTexto = telaCheck.getAttribute('data-item') || '';
				var producto = telaCheck.getAttribute('data-producto') || '';

				telasIniciales.push(telaCheck);
				itemsEnPedido.push({
					tipo: 'tela',
					articulo: articulo,
					rango: rango,
					itemTexto: itemTexto,
					producto: producto,
					precio: 0,
					orden: contadorOrdenSeleccion
				});
			});

			document.querySelectorAll('.check-producto[data-tipo="producto"]:checked').forEach(function (prodCheck) {
				contadorOrdenSeleccion += 1;
				var articulo = prodCheck.getAttribute('data-articulo') || '';
				var rango = prodCheck.getAttribute('data-rango') || '';
				var itemTexto = prodCheck.getAttribute('data-item') || '';
				var producto = prodCheck.getAttribute('data-producto') || '';
				var precio = parseFloat(prodCheck.getAttribute('data-precio') || '0');
				var grupoFila = prodCheck.getAttribute('data-grupo-fila') || '';

				itemsEnPedido.push({
					tipo: 'producto',
					articulo: articulo,
					rango: rango,
					itemTexto: itemTexto,
					producto: producto,
					precio: isNaN(precio) ? 0 : precio,
					grupoFilaBase: grupoFila,
					orden: contadorOrdenSeleccion
				});
			});

			// Si hay múltiples telas marcadas inicialmente, dejamos solo una activa en la interfaz de telas
			if (telasIniciales.length > 1) {
				for (var i = 1; i < telasIniciales.length; i++) {
					telasIniciales[i].checked = false;
				}
			}
		};

		inicializarItemsDesdeDOM();
		actualizarProductosPorRango();
		actualizarListaPedido();
	</script>
</body>
</html>
