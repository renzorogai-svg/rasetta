<?php
/** 11-08-2026
 * inicio.php
 *
 * Página de inicio del administrador.
 *
 * Muestra opciones para gestionar productos, telas y clientes.
 *
 * @package Administrador
 */
require_once __DIR__ . '/conexion.php';

$usuarioBuscado = '';
$mensaje = '';
$clientesDisponibles = [];
$rutaFondo = __DIR__ . '/fotos/fondo.jpg';
$versionFondo = file_exists($rutaFondo) ? (string) filemtime($rutaFondo) : (string) time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$usuarioBuscado = trim($_POST['usuario'] ?? '');

	if ($usuarioBuscado === '') {
		$mensaje = 'Ingrese un usuario para buscar.';
	} else {
		header('Location: ver_cliente.php?usuario=' . urlencode($usuarioBuscado));
		exit;
	}
}

$stmtClientes = mysqli_prepare($conexion, "SELECT DISTINCT usuario, nombre FROM cliente WHERE TRIM(usuario) <> '' ORDER BY nombre ASC, usuario ASC");

if ($stmtClientes) {
	mysqli_stmt_execute($stmtClientes);
	$resultadoClientes = mysqli_stmt_get_result($stmtClientes);

	if ($resultadoClientes) {
		while ($cliente = mysqli_fetch_assoc($resultadoClientes)) {
			$clientesDisponibles[] = [
				'usuario' => (string) ($cliente['usuario'] ?? ''),
				'nombre' => (string) ($cliente['nombre'] ?? '')
			];
		}
	}

	mysqli_stmt_close($stmtClientes);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Administrador</title>
	<style>
		:root {
			--fondo: #f2f6fb;
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

		h1 {
			margin: 0 0 22px;
			text-align: center;
			font-size: 1.9rem;
			letter-spacing: 0.3px;
		}

		.grid {
			display: grid;
			grid-template-columns: 1fr;
			gap: 16px;
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

		.titulo-tarjeta {
			margin: 0;
			font-size: 1.08rem;
			line-height: 1.35;
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
			transition: background 0.2s ease;
		}

		.boton:hover,
		.boton:focus-visible {
			background: var(--acento-hover);
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

		.campo label {
			font-size: 0.9rem;
			font-weight: 600;
		}

		.campo input {
			padding: 9px 10px;
			border: 1px solid var(--borde);
			border-radius: 8px;
			font-size: 0.95rem;
		}

		.lista-clientes {
			display: none;
			margin-top: 8px;
			border: 1px solid var(--borde);
			border-radius: 10px;
			background: #ffffff;
			box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
			max-height: 240px;
			overflow-y: auto;
		}

		.item-cliente {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 10px;
			padding: 10px 12px;
			text-decoration: none;
			color: var(--texto);
			border-bottom: 1px solid #eef3f8;
		}

		.item-cliente:last-child {
			border-bottom: 0;
		}

		.item-cliente:hover,
		.item-cliente:focus-visible {
			background: #f3f8ff;
		}

		.nombre-cliente {
			font-weight: 600;
		}

		.usuario-cliente {
			font-size: 0.85rem;
			color: #4b5563;
		}

		.campo select {
			padding: 9px 10px;
			border: 1px solid var(--borde);
			border-radius: 8px;
			font-size: 0.95rem;
			background: #ffffff;
		}

		.boton-form {
			border: 0;
			cursor: pointer;
		}

		.mensaje {
			margin: 0;
			font-weight: 600;
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

		.acciones-form {
			display: flex;
			gap: 10px;
			flex-wrap: wrap;
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

			.fila-dato {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
	<main class="contenedor">
		<h1>Administrador</h1>

		<section class="grid">
			<article class="tarjeta">
				<h2 class="titulo-tarjeta">Productos</h2>
				<a class="boton" href="listini.php">Lista precios</a>
			</article>

			<article class="tarjeta">
				<h2 class="titulo-tarjeta">Muestrario telas</h2>
				<a class="boton" href="telas.php">Ir a telas</a>
			</article>

			<article class="tarjeta">
				<h2 class="titulo-tarjeta">Buscar cliente</h2>
				<form class="formulario" method="post" action="">
					<div class="campo">
						<label for="usuario">Usuario</label>
						<input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuarioBuscado, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Ingrese el usuario" required autocomplete="off">

						<?php if (!empty($clientesDisponibles)): ?>
							<div class="lista-clientes" id="lista-clientes">
								<?php foreach ($clientesDisponibles as $cliente): ?>
									<?php $textoCliente = trim($cliente['nombre'] ?? '') !== '' ? $cliente['nombre'] : $cliente['usuario']; ?>
									<a class="item-cliente" href="ver_cliente.php?usuario=<?php echo urlencode($cliente['usuario']); ?>">
										<span class="nombre-cliente"><?php echo htmlspecialchars($textoCliente, ENT_QUOTES, 'UTF-8'); ?></span>
										<span class="usuario-cliente"><?php echo htmlspecialchars($cliente['usuario'], ENT_QUOTES, 'UTF-8'); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<div class="acciones-form">
						<button class="boton boton-form" type="submit">Buscar cliente</button>
					</div>
				</form>

				<?php if ($mensaje !== ''): ?>
					<p class="mensaje"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
				<?php endif; ?>
			</article>

			<article class="tarjeta">
				<h2 class="titulo-tarjeta">Agregar cliente</h2>
				<a class="boton" href="agrega_cliente.php">Agregar cliente</a>
			</article>
		</section>
	</main>

	<script>
		document.addEventListener('DOMContentLoaded', function () {
			const input = document.getElementById('usuario');
			const lista = document.getElementById('lista-clientes');

			if (!input || !lista) {
				return;
			}

			const mostrarLista = function () {
				lista.style.display = 'block';
			};

			const ocultarLista = function () {
				lista.style.display = 'none';
			};

			input.addEventListener('focus', mostrarLista);
			input.addEventListener('click', mostrarLista);
			input.addEventListener('input', mostrarLista);

			document.addEventListener('click', function (event) {
				if (!input.contains(event.target) && !lista.contains(event.target)) {
					ocultarLista();
				}
			});
		});
	</script>
</body>
</html>
