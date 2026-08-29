<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function agregarHojaTabla(Spreadsheet $libro, string $tabla, string $nombreHoja, bool $primeraHoja = false): void
{
	global $conexion;

	$resultado = mysqli_query($conexion, "SELECT * FROM `$tabla`");
	if (!$resultado) {
		throw new RuntimeException('No se pudo leer la tabla ' . $tabla . ': ' . mysqli_error($conexion));
	}

	$columnas = [];
	$campos = mysqli_fetch_fields($resultado);
	foreach ($campos as $campo) {
		$columnas[] = $campo->name;
	}

	$hoja = $primeraHoja ? $libro->getActiveSheet() : $libro->createSheet();
	$hoja->setTitle($nombreHoja);
	$hoja->fromArray($columnas, null, 'A1');

	$fila = 2;
	while ($registro = mysqli_fetch_assoc($resultado)) {
		$hoja->fromArray(array_values($registro), null, 'A' . $fila);
		$fila++;
	}

	$ultimaColumna = $hoja->getHighestColumn();
	$hoja->getStyle('A1:' . $ultimaColumna . '1')->getFont()->setBold(true);
	$hoja->freezePane('A2');
	foreach (range(1, count($columnas)) as $columna) {
		$hoja->getColumnDimensionByColumn($columna)->setAutoSize(true);
	}

	mysqli_free_result($resultado);
}

function descargarExcel(string $tipo): void
{
	global $conexion;

	$libro = new Spreadsheet();
	$nombreArchivo = '';

	if ($tipo === 'clientes') {
		agregarHojaTabla($libro, 'cliente', 'Clientes', true);
		$nombreArchivo = 'backup_clientes.xlsx';
	} elseif ($tipo === 'telas') {
		agregarHojaTabla($libro, 'telas', 'Telas', true);
		$nombreArchivo = 'backup_telas.xlsx';
	} elseif ($tipo === 'listini') {
		agregarHojaTabla($libro, 'ropadia', 'Ropadia', true);
		agregarHojaTabla($libro, 'unprecio', 'Un precio');
		agregarHojaTabla($libro, 'sobretodo', 'Sobretodo');

		$resultadoTablas = mysqli_query($conexion, "SHOW TABLES LIKE 'accesorios'");
		if ($resultadoTablas && mysqli_num_rows($resultadoTablas) > 0) {
			agregarHojaTabla($libro, 'accesorios', 'Accesorios');
		}
		if ($resultadoTablas) {
			mysqli_free_result($resultadoTablas);
		}
		$nombreArchivo = 'backup_listini.xlsx';
	} else {
		throw new InvalidArgumentException('Tipo de backup no válido.');
	}

	$libro->setActiveSheetIndex(0);
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
	header('Cache-Control: max-age=0');

	$escritor = new Xlsx($libro);
	$escritor->save('php://output');
	$libro->disconnectWorksheets();
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
	try {
		descargarExcel((string) $_POST['backup']);
	} catch (Throwable $error) {
		http_response_code(500);
		$mensajeError = htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Backup</title>
	<style>
		:root {
			--encabezado: #0f172a;
			--fondo: #e2e8f0;
			--texto: #1e293b;
			--acento: #0284c7;
			--acento-hover: #0369a1;
			--borde: #cbd5e1;
		}

		* { box-sizing: border-box; }

		body {
			min-height: 100vh;
			margin: 0;
			background: var(--fondo);
			color: var(--texto);
			font-family: "Segoe UI", system-ui, sans-serif;
		}

		.barra-superior {
			display: flex;
			align-items: center;
			gap: 12px;
			min-height: 58px;
			padding: 10px 20px;
			background: var(--encabezado);
		}

		.barra-superior h1 {
			margin: 0 auto 0 0;
			color: #fff;
			font-size: 1.1rem;
		}

		.boton-backup {
			padding: 8px 14px;
			border: 1px solid rgba(255, 255, 255, .2);
			border-radius: 6px;
			background: rgba(255, 255, 255, .1);
			color: #fff;
			font: inherit;
			cursor: pointer;
		}

		.boton-backup:hover { background: var(--acento); }

		.contenido {
			display: flex;
			flex-direction: column;
			min-height: calc(100vh - 58px);
			align-items: center;
			justify-content: center;
			padding: 32px 20px;
		}

		.tarjeta-informacion {
			width: min(100%, 680px);
			padding: 34px 38px;
			border: 1px solid var(--borde);
			border-radius: 10px;
			background: #ffffff;
			box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
			text-align: center;
		}

		.tarjeta-informacion h2 {
			margin: 0 0 16px;
			font-size: 1.45rem;
		}

		.tarjeta-informacion p {
			margin: 0 0 16px;
			line-height: 1.6;
		}

		.tarjeta-informacion p:last-child { margin-bottom: 0; }

		.tarjeta-informacion .nota { color: #475569; }

		.aviso-error {
			width: min(100%, 680px);
			padding: 12px;
			border: 1px solid #fca5a5;
			background: #fef2f2;
			color: #991b1b;
		}

		@media (max-width: 700px) {
			.barra-superior { flex-wrap: wrap; }
			.barra-superior h1 { width: 100%; }
			.boton-backup { flex: 1; }
			.tarjeta-informacion { padding: 26px 22px; }
		}
	</style>
</head>
<body>
	<header class="barra-superior">
		<h1>Copias de seguridad</h1>
		<a class="boton-backup" href="inicio.php">Volver</a>
		<form method="post"><button class="boton-backup" type="submit" name="backup" value="clientes">Guardar clientes</button></form>
		<form method="post"><button class="boton-backup" type="submit" name="backup" value="listini">Guardar listini</button></form>
		<form method="post"><button class="boton-backup" type="submit" name="backup" value="telas">Guardar telas</button></form>
	</header>

	<main class="contenido">
		<section class="tarjeta-informacion">
			<h2>Copia de seguridad local de la base de datos</h2>
			<p>Este módulo descarga la base de datos actual del servidor en la nube y la guarda en formato Excel en este dispositivo.</p>
			<p class="nota"><em>Nota:</em> Los cambios realizados en la nube no se actualizan automáticamente en este archivo; la actualización debe realizarse manualmente desde este módulo.</p>
		</section>
		<?php if (isset($mensajeError)): ?>
			<p class="aviso-error"><?php echo $mensajeError; ?></p>
		<?php endif; ?>
	</main>
</body>
</html>
