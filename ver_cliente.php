<?php
/* 11-08-2026
	Archivo: ver_cliente.php
	Descripcion: Muestra los detalles de un cliente y sus pedidos.
*/ 
require_once __DIR__ . '/conexion.php';

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

if ($pedidoSeleccionado === 'nuevo' && $usuarioBuscado !== '') {
	header('Location: seleccion_producto.php?usuario=' . urlencode($usuarioBuscado) . '&pedido=nuevo');
	exit;
}

if ($usuarioBuscado === '') {
	$mensaje = 'No se recibio un usuario para buscar.';
} else {
	$stmt = mysqli_prepare($conexion, 'SELECT * FROM cliente WHERE usuario = ? ORDER BY pedido ASC, Id ASC');

	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 's', $usuarioBuscado);
		mysqli_stmt_execute($stmt);
		$resultado = mysqli_stmt_get_result($stmt);

		if ($resultado && mysqli_num_rows($resultado) > 0) {
			while ($fila = mysqli_fetch_assoc($resultado)) {
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
					'precio' => (string) ($fila['precio'] ?? '')
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
					<?php endif; ?>
					<?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
						<button class="boton boton-peligro" type="submit" form="formEliminarPedido" onclick="return confirm('Se eliminara todo el pedido seleccionado. Desea continuar?');">Eliminar pedido</button>
					<?php endif; ?>
					<a class="boton" href="inicio.php">Volver</a>
				</div>
			</form>

			<?php if ($usuarioBuscado !== '' && ctype_digit($pedidoSeleccionado) && (int) $pedidoSeleccionado > 0): ?>
				<form id="formEliminarPedido" method="post" action="ver_cliente.php">
					<input type="hidden" name="usuario" value="<?php echo htmlspecialchars($usuarioBuscado, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="pedido" value="<?php echo htmlspecialchars($pedidoSeleccionado, ENT_QUOTES, 'UTF-8'); ?>">
					<input type="hidden" name="eliminar_pedido" value="1">
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
						<?php foreach ($productosPedido as $item): ?>
							<?php
								$precioProducto = $item['precio'] ?? '';
								$precioProductoMostrar = is_numeric($precioProducto) ? formatear_precio_mostrar($precioProducto) : (string) $precioProducto;
							?>
							<div class="fila-producto">
								<div class="nombre-producto"><?php echo htmlspecialchars((string) ($item['producto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
								<div class="precio-producto"><?php echo htmlspecialchars($precioProductoMostrar, ENT_QUOTES, 'UTF-8'); ?></div>
							</div>
						<?php endforeach; ?>
						<div class="fila-producto fila-total">
							<div class="nombre-producto">Total</div>
							<div class="precio-producto"><?php echo htmlspecialchars(formatear_precio_mostrar($totalPedido), ENT_QUOTES, 'UTF-8'); ?></div>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</section>
	</main>
</body>
</html>
