<?php
/*  24-08-2026  desde PC
archivo: envia_brioni.php
Descripcion: Genera un PDF con los datos del cliente y lo envía a través de Whats
*/
ob_start();

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/vendor/autoload.php';

use Mpdf\Mpdf;

$usuario = trim($_GET['usuario'] ?? '');
$cliente = null;
$mensaje = '';
$tipoMensaje = '';

if ($usuario === '') {
	$mensaje = 'No se recibio un usuario para buscar.';
} else {
	$stmt = mysqli_prepare($conexion, 'SELECT `ID cliente`, nombre, usuario, telefono, direccion, correo FROM cliente WHERE usuario = ? LIMIT 1');

	if ($stmt) {
		mysqli_stmt_bind_param($stmt, 's', $usuario);
		mysqli_stmt_execute($stmt);
		$resultado = mysqli_stmt_get_result($stmt);
		$cliente = $resultado ? mysqli_fetch_assoc($resultado) : null;
		mysqli_stmt_close($stmt);

		if (!$cliente) {
			$mensaje = 'No se encontro un cliente con ese usuario.';
		}
	} else {
		$mensaje = 'Ocurrio un error al preparar la consulta.';
	}
}

$camposCliente = [
	'ID cliente' => $cliente['ID cliente'] ?? 'vacio',
	'Nombre' => $cliente['nombre'] ?? 'vacio',
	'Usuario' => $cliente['usuario'] ?? 'vacio',
	'Telefono' => $cliente['telefono'] ?? 'vacio',
	'Direccion' => $cliente['direccion'] ?? 'vacio',
	'Correo' => $cliente['correo'] ?? 'vacio'
];

if (isset($_GET['exportar_pdf']) && $_GET['exportar_pdf'] === '1' && $cliente) {
	try {
		$escaparPdf = static function ($valor): string {
			return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
		};

		$filasPdf = '';
		foreach ($camposCliente as $etiqueta => $valor) {
			$filasPdf .= '<tr><td class="etiqueta">' . $escaparPdf($etiqueta) . '</td><td>' . $escaparPdf($valor) . '</td></tr>';
		}

		$nombreCliente = (string) ($cliente['nombre'] ?? $usuario);
		$htmlPdf = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
			body { font-family: sans-serif; color: #1f2937; font-size: 10pt; }
			h1 { color: #0f766e; border-bottom: 2px solid #0f766e; padding-bottom: 8px; }
			table { width: 100%; border-collapse: collapse; }
			td { border: 1px solid #d6dee8; padding: 8px; }
			.etiqueta { width: 25%; font-weight: bold; background: #eef6f4; }
		</style></head><body><h1>Datos del cliente</h1><table>' . $filasPdf . '</table></body></html>';

		$directorioTemporal = __DIR__ . '/tmp';
		if (!is_dir($directorioTemporal) && !mkdir($directorioTemporal, 0775, true) && !is_dir($directorioTemporal)) {
			throw new RuntimeException('No se pudo preparar la carpeta temporal del PDF.');
		}

		$archivoTemporal = tempnam($directorioTemporal, 'rasetta_cliente_');
		if ($archivoTemporal === false) {
			throw new RuntimeException('No se pudo preparar el archivo PDF.');
		}

		$pdf = new Mpdf(['format' => 'A4', 'tempDir' => $directorioTemporal]);
		$pdf->SetTitle('Datos del cliente ' . $nombreCliente);
		$pdf->WriteHTML($htmlPdf);
		$pdf->Output($archivoTemporal, \Mpdf\Output\Destination::FILE);

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		$nombreArchivo = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $nombreCliente ?: 'cliente') . '.pdf';
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
		header('Content-Length: ' . filesize($archivoTemporal));
		header('Cache-Control: max-age=0');
		readfile($archivoTemporal);
		unlink($archivoTemporal);
		exit;
	} catch (Throwable $errorPdf) {
		http_response_code(500);
		$mensaje = 'No se pudo generar el PDF.';
	}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Datos del cliente</title>
	<style>
		:root {
			--texto: #1f2937;
			--borde: #d6dee8;
			--acento: #0f766e;
			--acento-hover: #0b5f59;
		}

		* { box-sizing: border-box; }

		body {
			margin: 0;
			min-height: 100dvh;
			padding: 24px;
			background: #e9eef9;
			color: var(--texto);
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
		}

		main {
			width: min(760px, 100%);
			margin: 0 auto;
			padding: 24px;
			background: #ffffff;
			border: 1px solid var(--borde);
			border-radius: 12px;
			box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
		}

		h1 { margin-top: 0; font-size: 1.4rem; }

		.datos { display: grid; gap: 8px; }

		.fila {
			display: grid;
			grid-template-columns: minmax(140px, 220px) 1fr;
			gap: 10px;
			padding: 9px 10px;
			background: #f5f9ff;
			border: 1px solid #dde7f5;
			border-radius: 8px;
		}

		.etiqueta { font-weight: 700; }
		.valor { word-break: break-word; }
		.mensaje { font-weight: 600; color: #b91c1c; }
		.mensaje.ok { color: #166534; }

		.acciones { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 18px; }

		.boton {
			display: inline-block;
			padding: 9px 13px;
			border: 0;
			border-radius: 8px;
			background: var(--acento);
			color: #ffffff;
			font-weight: 600;
			text-decoration: none;
			cursor: pointer;
		}

		.boton:hover, .boton:focus-visible { background: var(--acento-hover); }

		@media (max-width: 640px) {
			body { padding: 12px; }
			main { padding: 18px; }
			.fila { grid-template-columns: 1fr; }
		}
	</style>
</head>
<body>
	<main>
		<h1>Datos del cliente</h1>

		<?php if ($mensaje !== ''): ?>
			<p class="mensaje <?php echo htmlspecialchars($tipoMensaje ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php elseif ($cliente): ?>
			<div class="datos">
				<?php foreach ($camposCliente as $etiqueta => $valor): ?>
					<div class="fila">
						<div class="etiqueta"><?php echo htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8'); ?></div>
						<div class="valor"><?php echo htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8'); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="acciones">
				<a class="boton" href="envia_brioni.php?<?php echo htmlspecialchars(http_build_query(['usuario' => $usuario, 'exportar_pdf' => '1']), ENT_QUOTES, 'UTF-8'); ?>" download>Guardar PDF</a>
				<a class="boton" href="ver_cliente.php?<?php echo htmlspecialchars(http_build_query(['usuario' => $usuario]), ENT_QUOTES, 'UTF-8'); ?>">Volver</a>
			</div>
		<?php endif; ?>
	</main>
</body>
</html>
