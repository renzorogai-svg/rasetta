<?php 
/*
06-08-2026
*/
require_once __DIR__ . '/conexion.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Listini Ropadia</title>
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
			margin: 0 0 24px;
			font-size: 1.9rem;
			text-align: center;
			letter-spacing: 0.4px;
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

		.acordeon-linea:focus-visible {
			outline: 2px solid #0f766e;
			outline-offset: -2px;
		}

		.acordeon-flecha {
			font-size: 0.85rem;
			transition: transform 0.2s ease;
			flex: 0 0 auto;
		}

		.bloque-tabla.abierta .acordeon-flecha {
			transform: rotate(180deg);
		}

		.subtitulo {
			margin: 0 0 12px;
			color: var(--muted);
			font-size: 0.95rem;
		}

		.tabla-wrapper {
			width: 100%;
			overflow-x: auto;
			display: none;
			border-top: 1px solid var(--borde);
		}

		.bloque-tabla.abierta .tabla-wrapper {
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

		@media (max-width: 640px) {
			.contenedor {
				padding: 16px;
				border-radius: 12px;
			}

			.acordeon-linea {
				padding: 12px 12px;
				font-size: 0.98rem;
			}
		}
	</style>
</head>
<body>
	<main class="contenedor">
		<div class="acciones">
			<a class="boton-inicio" href="inicio.php">Volver a inicio</a>
		</div>
		<h3>Daywear (ropa de dia)</h3>

		<?php foreach ($productosObjetivo as $producto): ?>
			<section class="bloque-tabla">
				<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
					<span><?php echo htmlspecialchars($producto['titulo'], ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="acordeon-flecha">▼</span>
				</div>
				<?php if (!empty($datosPorProducto[$producto['db']])): ?>
					<div class="tabla-wrapper">
						<table>
							<thead>
								<tr>
									<th>Rango</th>
									<th>Un botón</th>
									<th>Dos botones</th>
									<th>Especial</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($datosPorProducto[$producto['db']] as $fila): ?>
									<tr>
										<td><?php echo (int) $fila['rango']; ?></td>
										<td><?php echo (int) $fila['unboton']; ?></td>
										<td><?php echo (int) $fila['dosbotones']; ?></td>
										<td><?php echo (int) $fila['especial']; ?></td>
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

		<!--<h1>Listado Un Precio</h1> -->

		<?php foreach ($productosUnPrecio as $productoUnPrecio): ?>
			<?php if ($productoUnPrecio['db'] === 'esmoquin'): ?>
				<h3>Eveningwear (ropa de noche)</h3>
			<?php endif; ?>
			<section class="bloque-tabla">
				<div class="acordeon-linea" role="button" tabindex="0" aria-expanded="false">
					<span><?php echo htmlspecialchars($productoUnPrecio['titulo'], ENT_QUOTES, 'UTF-8'); ?></span>
					<span class="acordeon-flecha">▼</span>
				</div>
				<?php if (!empty($datosUnPrecio[$productoUnPrecio['db']])): ?>
					<div class="tabla-wrapper">
						<table>
							<thead>
								<tr>
									<th>Rango</th>
									<th>Precio</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($datosUnPrecio[$productoUnPrecio['db']] as $filaUnPrecio): ?>
									<tr>
										<td><?php echo (int) $filaUnPrecio['rango']; ?></td>
										<td><?php echo (int) $filaUnPrecio['precio']; ?></td>
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
						<span>Overcoat (Sobretodos)</span>
						<span class="acordeon-flecha">▼</span>
					</div>
					<?php if (!empty($datosSobretodo)): ?>
						<div class="tabla-wrapper">
							<table>
								<thead>
									<tr>
										<th>Rango</th>
										<th>Categoria 1</th>
										<th>Categoria 2</th>
										<th>Categoria 3</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($datosSobretodo as $filaSobretodo): ?>
										<tr>
											<td><?php echo (int) $filaSobretodo['rango']; ?></td>
											<td><?php echo (int) $filaSobretodo['categoria1']; ?></td>
											<td><?php echo (int) $filaSobretodo['categoria2']; ?></td>
											<td><?php echo (int) $filaSobretodo['categoria3']; ?></td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else: ?>
						<p class="sin-datos">No hay registros en la tabla sobretodo.</p>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		<?php endforeach; ?>
	</main>

	<script>
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
	</script>
</body>
</html>

