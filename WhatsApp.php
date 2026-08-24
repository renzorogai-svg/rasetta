<?php
/* 24-08-2026  desde Laptop
    Archivo: WhatsApp.php
    Descripcion: prepara el PDF del pedido para compartirlo por WhatsApp.
*/ 
$usuario = trim($_GET['usuario'] ?? '');
$pedido = trim($_GET['pedido'] ?? '');
$telefonoOriginal = trim($_GET['telefono'] ?? '');

$telefono = preg_replace('/\D+/', '', $telefonoOriginal);
if (substr($telefono, 0, 2) === '00') {
    $telefono = substr($telefono, 2);
}
if (substr($telefono, 0, 1) === '0') {
    $telefono = '58' . substr($telefono, 1);
}

$datosValidos = $usuario !== '' && ctype_digit($pedido) && (int) $pedido > 0 && strlen($telefono) >= 10;
$mensaje = '';
$pdfUrl = '';
$urlWhatsApp = '';

if ($datosValidos) {
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $protocolo . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $parametrosPdf = http_build_query([
        'usuario' => $usuario,
        'pedido' => $pedido,
        'exportar_pdf' => '1'
    ]);
    $pdfUrl = $baseUrl . '/ver_cliente.php?' . $parametrosPdf;
    $mensaje = 'Pedido ' . $pedido;
    $urlWhatsApp = 'https://api.whatsapp.com/send?phone=' . $telefono . '&text=' . rawurlencode($mensaje);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enviar por WhatsApp</title>
    <style>
        :root {
            --texto: #1f2937;
            --borde: #d6dee8;
            --acento: #128c7e;
            --acento-hover: #075e54;
            --fondo: #eef5f4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: var(--fondo);
            color: var(--texto);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .tarjeta {
            width: min(560px, 100%);
            padding: 24px;
            background: #ffffff;
            border: 1px solid var(--borde);
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 18px;
            font-size: 1.35rem;
        }

        p {
            line-height: 1.5;
        }

        .dato {
            margin: 8px 0;
            padding: 8px 10px;
            background: #f5f9ff;
            border: 1px solid #dde7f5;
            border-radius: 7px;
            overflow-wrap: anywhere;
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 9px 13px;
            border-radius: 8px;
            background: var(--acento);
            color: #ffffff;
            text-decoration: none;
            font-weight: 600;
        }

        .boton:hover,
        .boton:focus-visible {
            background: var(--acento-hover);
        }

        .error {
            color: #b91c1c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <h1>Enviar pedido por WhatsApp</h1>

        <?php if (!$datosValidos): ?>
            <p class="error">No se recibieron correctamente el teléfono, el usuario o el pedido.</p>
            <div class="acciones">
                <a class="boton" href="ver_cliente.php">Volver</a>
            </div>
        <?php else: ?>
            <p>El PDF del pedido esta listo para enviarlo como archivo a este número:</p>
            <div class="dato"><strong>Teléfono:</strong> <?php echo htmlspecialchars($telefonoOriginal, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="dato"><strong>Pedido:</strong> <?php echo htmlspecialchars($pedido, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="acciones">
                <button class="boton" type="button" id="compartir-pdf">Enviar PDF a WhatsApp</button>
                <a class="boton" href="<?php echo htmlspecialchars($pdfUrl, ENT_QUOTES, 'UTF-8'); ?>" download="pedido_<?php echo htmlspecialchars($pedido, ENT_QUOTES, 'UTF-8'); ?>.pdf">Descargar PDF</a>
                <a class="boton" href="<?php echo htmlspecialchars($urlWhatsApp, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Abrir WhatsApp</a>
                <a class="boton" href="ver_cliente.php?<?php echo htmlspecialchars(http_build_query(['usuario' => $usuario, 'pedido' => $pedido]), ENT_QUOTES, 'UTF-8'); ?>">Volver</a>
            </div>
            <p id="estado-compartir">Pulsa el botón para seleccionar WhatsApp y adjuntar el PDF como documento.</p>
        <?php endif; ?>
    </main>
    <?php if ($datosValidos): ?>
        <script>
            const botonCompartir = document.getElementById('compartir-pdf');
            const estadoCompartir = document.getElementById('estado-compartir');
            const pdfUrl = <?php echo json_encode($pdfUrl, JSON_UNESCAPED_SLASHES); ?>;
            const telefonoWhatsApp = <?php echo json_encode($urlWhatsApp, JSON_UNESCAPED_SLASHES); ?>;
            const nombrePdf = <?php echo json_encode('pedido_' . $pedido . '.pdf'); ?>;

            botonCompartir.addEventListener('click', async function () {
                botonCompartir.disabled = true;
                estadoCompartir.textContent = 'Preparando el PDF...';

                try {
                    const respuesta = await fetch(pdfUrl, { credentials: 'same-origin' });
                    if (!respuesta.ok) {
                        throw new Error('No se pudo generar el PDF.');
                    }

                    const archivoPdf = new File([await respuesta.blob()], nombrePdf, { type: 'application/pdf' });
                    if (navigator.share && (!navigator.canShare || navigator.canShare({ files: [archivoPdf] }))) {
                        await navigator.share({
                            files: [archivoPdf],
                            title: 'Pedido ' + <?php echo json_encode($pedido); ?>,
                            text: 'Pedido ' + <?php echo json_encode($pedido); ?>
                        });
                        estadoCompartir.textContent = 'PDF compartido correctamente.';
                    } else {
                        const enlaceDescarga = document.createElement('a');
                        enlaceDescarga.href = URL.createObjectURL(archivoPdf);
                        enlaceDescarga.download = nombrePdf;
                        enlaceDescarga.click();
                        window.open(telefonoWhatsApp, '_blank', 'noopener');
                        estadoCompartir.textContent = 'PDF descargado. Adjuntalo manualmente en el chat de WhatsApp.';
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        estadoCompartir.textContent = 'No se pudo compartir automaticamente. Descarga el PDF y adjuntalo en WhatsApp.';
                    }
                } finally {
                    botonCompartir.disabled = false;
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
