<?php
/*  20-08-2026  desde Laptop
*/
require_once 'conexion.php';

$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_tela'])) {
    $articulo = trim($_POST['articulo'] ?? '');
    $muestrario = trim($_POST['muestrario'] ?? '');
    $composicion = trim($_POST['composicion'] ?? '');
    $peso = trim($_POST['peso'] ?? '');
    $rango = trim($_POST['rango'] ?? '');
    $pagina = trim($_POST['pagina'] ?? '');
    $foto = $_FILES['foto'] ?? null;

    if ($articulo === '' || $muestrario === '' || $composicion === '' || $peso === '' || $rango === '' || $pagina === '') {
        $mensaje = 'Complete todos los campos antes de guardar.';
        $tipoMensaje = 'error';
    } elseif (!ctype_digit($peso) || !ctype_digit($rango)) {
        $mensaje = 'Peso y Rango deben ser valores numericos enteros.';
        $tipoMensaje = 'error';
    } elseif ($foto === null || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($foto['tmp_name'])) {
        $mensaje = 'Seleccione una foto valida antes de guardar.';
        $tipoMensaje = 'error';
    } elseif (($foto['size'] ?? 0) > 8 * 1024 * 1024) {
        $mensaje = 'La foto no puede superar los 8 MB.';
        $tipoMensaje = 'error';
    } else {
        $informacionImagen = @getimagesize($foto['tmp_name']);
        $extensionesPermitidas = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif'
        ];
        $tipoImagen = is_array($informacionImagen) ? ($informacionImagen[2] ?? 0) : 0;

        if (!isset($extensionesPermitidas[$tipoImagen])) {
            $mensaje = 'Solo se permiten imagenes JPG, PNG, WEBP o GIF.';
            $tipoMensaje = 'error';
        } else {
            $directorioFotos = __DIR__ . '/fotos';
            $nombreFoto = 'tela_' . bin2hex(random_bytes(12)) . '.' . $extensionesPermitidas[$tipoImagen];
            $rutaFoto = $directorioFotos . '/' . $nombreFoto;
            $fotoMovida = is_dir($directorioFotos) || mkdir($directorioFotos, 0755, true);
            $fotoMovida = $fotoMovida && move_uploaded_file($foto['tmp_name'], $rutaFoto);

            if (!$fotoMovida) {
                $mensaje = 'No se pudo guardar la foto seleccionada.';
                $tipoMensaje = 'error';
            } else {
                $stmtGuardar = mysqli_prepare($conexion, 'INSERT INTO telas (articulo, muestrario, composicion, pero, rango, pagina, foto) VALUES (?, ?, ?, ?, ?, ?, ?)');

                if ($stmtGuardar) {
                    $pesoEntero = (int) $peso;
                    $rangoEntero = (int) $rango;
                    mysqli_stmt_bind_param($stmtGuardar, 'sssiiss', $articulo, $muestrario, $composicion, $pesoEntero, $rangoEntero, $pagina, $nombreFoto);
                    $guardadoCorrecto = mysqli_stmt_execute($stmtGuardar);
                    mysqli_stmt_close($stmtGuardar);
                } else {
                    $guardadoCorrecto = false;
                }

                if ($guardadoCorrecto) {
                    header('Location: telas.php');
                    exit;
                } else {
                    unlink($rutaFoto);
                    $mensaje = 'No se pudo guardar la tela en la base de datos.';
                    $tipoMensaje = 'error';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar tela</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            padding: clamp(12px, 2.6vw, 24px);
            background: #f5f5f5;
            color: #2c3e50;
            font-family: Arial, sans-serif;
        }

        .contenedor {
            width: min(620px, 100%);
            margin: 0 auto;
            padding: 24px;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h1 {
            margin: 0 0 8px;
            text-align: center;
        }

        .subtitulo {
            margin: 0 0 22px;
            color: #64748b;
            text-align: center;
        }

        .mensaje {
            margin: 0 0 18px;
            padding: 10px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .mensaje.ok {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .mensaje.error {
            background: #ffebee;
            color: #c62828;
        }

        .boton-foto:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .datos-extraidos {
            display: grid;
            gap: 10px;
        }

        .datos-extraidos label {
            display: grid;
            gap: 5px;
            font-weight: 600;
        }

        .datos-extraidos input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b9cbd4;
            border-radius: 8px;
            font: inherit;
        }

        .formulario-foto {
            display: grid;
            gap: 14px;
        }

        .boton-foto,
        .boton-volver {
            display: inline-block;
            width: 100%;
            padding: 11px 14px;
            border: 0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
        }

        .boton-foto {
            background: #1e5f74;
            color: #ffffff;
        }

        .boton-foto:hover,
        .boton-foto:focus-visible {
            background: #17495a;
        }

        .boton-volver {
            margin-top: 12px;
            border: 1px solid #b9cbd4;
            background: #ffffff;
            color: #2c3e50;
        }

        .nombre-archivo {
            min-height: 20px;
            color: #64748b;
            font-size: 14px;
            text-align: center;
        }

        .vista-previa {
            display: block;
            width: min(100%, 360px);
            max-height: 280px;
            margin: 4px auto 0;
            border-radius: 8px;
            object-fit: contain;
        }

        @media (max-width: 640px) {
            .contenedor {
                padding: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="contenedor">
        <h1>Agregar tela</h1>
        <p class="subtitulo">Seleccione una foto para extraer el texto visible y guardarla con los datos de la tela.</p>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje <?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form class="formulario-foto" method="post" enctype="multipart/form-data">
            <input type="file" id="fotoOCR" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
            <label for="fotoOCR" class="boton-foto">Seleccionar foto para extraer texto</label>
            <div id="nombreArchivoOCR" class="nombre-archivo">No se ha seleccionado ninguna foto para OCR.</div>
            <button type="button" id="botonExtraerTexto" class="boton-foto" disabled>Extraer texto</button>
            <p id="estadoOCR" class="mensaje" hidden></p>
            <div class="datos-extraidos">
                <label for="articuloTela">Articulo
                    <input type="text" id="articuloTela" name="articulo" value="<?php echo htmlspecialchars($articulo ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label for="coleccionExtraida">Muestrario
                    <input type="text" id="coleccionExtraida" name="muestrario" value="<?php echo htmlspecialchars($muestrario ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label for="composicionExtraida">Composicion
                    <input type="text" id="composicionExtraida" name="composicion" value="<?php echo htmlspecialchars($composicion ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label for="pesoExtraido">Peso
                    <input type="text" id="pesoExtraido" name="peso" value="<?php echo htmlspecialchars($peso ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label for="rangoTela">Rango
                    <input type="number" id="rangoTela" name="rango" value="<?php echo htmlspecialchars($rango ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
                <label for="paginaTela">Pagina
                    <input type="text" id="paginaTela" name="pagina" value="<?php echo htmlspecialchars($pagina ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                </label>
            </div>
            <label for="foto" class="boton-foto">Agregar foto</label>
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif" hidden required>
            <div id="nombreArchivo" class="nombre-archivo">No se ha seleccionado ninguna foto para guardar.</div>
            <button type="submit" name="guardar_tela" class="boton-foto">Guardar</button>
            <img id="vistaPrevia" class="vista-previa" src="" alt="Vista previa de la foto" hidden>
        </form>

        <a class="boton-volver" href="telas.php">Volver al catalogo</a>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var campoFotoOCR = document.getElementById('fotoOCR');
            var nombreArchivoOCR = document.getElementById('nombreArchivoOCR');
            var campoFoto = document.getElementById('foto');
            var nombreArchivo = document.getElementById('nombreArchivo');
            var botonExtraerTexto = document.getElementById('botonExtraerTexto');
            var estadoOCR = document.getElementById('estadoOCR');
            var textoOCR = '';
            var coleccionExtraida = document.getElementById('coleccionExtraida');
            var composicionExtraida = document.getElementById('composicionExtraida');
            var pesoExtraido = document.getElementById('pesoExtraido');
            var vistaPrevia = document.getElementById('vistaPrevia');
            var archivoSeleccionado = null;

            var limpiarOCR = function (texto) {
                return texto
                    .replace(/[|]/g, 'I')
                    .replace(/\r/g, '')
                    .split('\n')
                    .map(function (linea) { return linea.replace(/\s+/g, ' ').trim(); })
                    .filter(function (linea) { return linea !== ''; })
                    .join('\n');
            };

            var extraerCampo = function (texto, patrones) {
                var lineas = texto.split('\n');
                for (var indice = 0; indice < lineas.length; indice += 1) {
                    for (var patron of patrones) {
                        var coincidencia = lineas[indice].match(patron);
                        if (coincidencia && coincidencia[1]) {
                            return coincidencia[1].trim();
                        }
                    }
                }
                return '';
            };

            var extraerDatosTela = function (texto) {
                var textoContinuo = texto.replace(/\s+/g, ' ').trim();
                var coleccion = extraerCampo(texto, [
                    /(?:colecci[oó]n|l[ií]nea)\s*[:\-]?\s*(.+)$/i,
                    /(SS\s*26.*?ABITO)/i,
                    /(SS\s*26.*?ULTIMATE)/i
                ]);
                var composicion = extraerCampo(texto, [
                    /(?:comp(?:osici[oó]n)?|composicion)\.?\s*[:\-]?\s*(\d+%?\s*WO[^\n]*)/i,
                    /(\d+\s*%?\s*W[O0]\s*SUPER\s*200['’]?s?)/i
                ]);
                var peso = extraerCampo(texto, [
                    /(?:peso|gr\.?)\s*[:\-]?\s*(\d{2,4})/i,
                    /\b(\d{3})\s*(?:gr\.?|g)\b/i
                ]);

                if (!coleccion) {
                    var coincidenciaColeccion = textoContinuo.match(/(SS\s*26.*?ABITO)/i);
                    coleccion = coincidenciaColeccion ? coincidenciaColeccion[1] : '';
                }

                if (!composicion && /SUPER\s*200/i.test(textoContinuo)) {
                    var porcentaje = textoContinuo.match(/(\d{2,3})\s*%/);
                    composicion = porcentaje ? porcentaje[1] + '% WO SUPER 200\'s' : '';
                }

                if (!peso) {
                    var coincidenciaPeso = textoContinuo.match(/\b(\d{3})\b(?=\s*(?:gr\.?|g)?)/i);
                    peso = coincidenciaPeso ? coincidenciaPeso[1] : '';
                }

                coleccionExtraida.value = coleccion || '';
                composicionExtraida.value = composicion || '';
                pesoExtraido.value = peso || '';
            };

            var extraerDatosPorColumnas = function (imagenOCR) {
                var ancho = imagenOCR.width;
                var alto = imagenOCR.height;
                var margenes = [0.02, 0.34, 0.67, 0.98];
                var lecturas = [];

                for (var indice = 0; indice < 3; indice += 1) {
                    var columna = document.createElement('canvas');
                    columna.width = Math.round(ancho * (margenes[indice + 1] - margenes[indice]));
                    columna.height = alto;
                    var contextoColumna = columna.getContext('2d');
                    contextoColumna.drawImage(
                        imagenOCR,
                        Math.round(ancho * margenes[indice]),
                        0,
                        columna.width,
                        alto,
                        0,
                        0,
                        columna.width,
                        alto
                    );
                    lecturas.push(Tesseract.recognize(columna, 'eng', { psm: 6 }));
                }

                return Promise.all(lecturas).then(function (resultados) {
                    var coleccion = resultados[0].data.text
                        .replace(/\s+/g, ' ')
                        .replace(/\s*\|\s*/g, ' ')
                        .trim();
                    var composicion = resultados[1].data.text
                        .replace(/\s+/g, ' ')
                        .replace(/\bW0\b/gi, 'WO')
                        .replace(/\b2002\b/g, "200's")
                        .trim();
                    var peso = resultados[2].data.text.match(/\b\d{2,4}\b/);

                    coleccion = coleccion.replace(/^(SS\s*26)\s+/i, 'SS26 ');
                    composicion = composicion.replace(/^(\d+)\s*%?\s*(WO\s+SUPER\s+200['’]?s?).*$/i, '$1% $2');

                    coleccionExtraida.value = coleccion;
                    composicionExtraida.value = composicion;
                    pesoExtraido.value = peso ? peso[0] : '';
                });
            };

            var prepararImagenOCR = function (archivo) {
                return new Promise(function (resolver, rechazar) {
                    var imagen = new Image();
                    var lector = new FileReader();

                    lector.onload = function () {
                        imagen.onload = function () {
                            var escala = 2;
                            var lienzo = document.createElement('canvas');
                            lienzo.width = imagen.naturalWidth * escala;
                            lienzo.height = imagen.naturalHeight * escala;
                            var contexto = lienzo.getContext('2d');
                            contexto.filter = 'grayscale(1) contrast(1.35)';
                            contexto.drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
                            resolver(lienzo);
                        };
                        imagen.onerror = rechazar;
                        imagen.src = lector.result;
                    };
                    lector.onerror = rechazar;
                    lector.readAsDataURL(archivo);
                });
            };

            if (campoFotoOCR && nombreArchivoOCR) {
                campoFotoOCR.addEventListener('change', function () {
                    archivoSeleccionado = campoFotoOCR.files.length > 0 ? campoFotoOCR.files[0] : null;
                    nombreArchivoOCR.textContent = archivoSeleccionado
                        ? archivoSeleccionado.name
                        : 'No se ha seleccionado ninguna foto para OCR.';
                    botonExtraerTexto.disabled = !archivoSeleccionado;

                    if (archivoSeleccionado) {
                        vistaPrevia.src = URL.createObjectURL(archivoSeleccionado);
                        vistaPrevia.hidden = false;
                        textoOCR = '';
                        coleccionExtraida.value = '';
                        composicionExtraida.value = '';
                        pesoExtraido.value = '';
                        estadoOCR.hidden = true;
                    }
                });
            }

            if (campoFoto && nombreArchivo) {
                campoFoto.addEventListener('change', function () {
                    nombreArchivo.textContent = campoFoto.files.length > 0
                        ? campoFoto.files[0].name
                        : 'No se ha seleccionado ninguna foto para guardar.';
                });
            }

            botonExtraerTexto.addEventListener('click', function () {
                if (!archivoSeleccionado) {
                    return;
                }

                botonExtraerTexto.disabled = true;
                estadoOCR.hidden = false;
                estadoOCR.className = 'mensaje';
                estadoOCR.textContent = 'Extrayendo texto, espere un momento...';

                prepararImagenOCR(archivoSeleccionado).then(function (imagenOCR) {
                    return Tesseract.createWorker('eng', 1, {
                        logger: function (datos) {
                            if (datos.status === 'recognizing text' && typeof datos.progress === 'number') {
                                estadoOCR.textContent = 'Extrayendo texto: ' + Math.round(datos.progress * 100) + '%';
                            }
                        }
                    }).then(function (worker) {
                        return worker.recognize(imagenOCR).then(function (resultado) {
                            return worker.terminate().then(function () {
                                return {
                                    imagenOCR: imagenOCR,
                                    resultado: resultado
                                };
                            });
                        });
                    });
                }).then(function (datosOCR) {
                    textoOCR = limpiarOCR(datosOCR.resultado.data.text);
                    return extraerDatosPorColumnas(datosOCR.imagenOCR).then(function () {
                        if (!coleccionExtraida.value || !composicionExtraida.value || !pesoExtraido.value) {
                            var datosAntesDelRespaldo = {
                                coleccion: coleccionExtraida.value,
                                composicion: composicionExtraida.value,
                                peso: pesoExtraido.value
                            };
                            extraerDatosTela(textoOCR);
                            coleccionExtraida.value = datosAntesDelRespaldo.coleccion || coleccionExtraida.value;
                            composicionExtraida.value = datosAntesDelRespaldo.composicion || composicionExtraida.value;
                            pesoExtraido.value = datosAntesDelRespaldo.peso || pesoExtraido.value;
                        }
                    });
                }).then(function () {
                    estadoOCR.className = 'mensaje ok';
                    estadoOCR.textContent = textoOCR !== ''
                        ? 'Texto extraido. Revise los tres campos y corrijalos si es necesario.'
                        : 'No se encontro texto legible en la foto.';
                }).catch(function () {
                    estadoOCR.className = 'mensaje error';
                    estadoOCR.textContent = 'No se pudo extraer el texto de la foto.';
                }).finally(function () {
                    botonExtraerTexto.disabled = false;
                });

            });
        });
    </script>
</body>
</html>
