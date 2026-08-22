<?php
/*  22-08-2026  desde Laptop
*/
require_once 'conexion.php';

$articuloBuscado = trim($_GET['articulo'] ?? '');
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_tela'])) {
    $idTelaEliminar = trim($_POST['id_tela'] ?? '');
    $articuloBuscado = trim($_POST['articulo'] ?? '');

    if (!ctype_digit($idTelaEliminar) || (int) $idTelaEliminar <= 0) {
        $mensaje = 'No se pudo eliminar: identificador de tela invalido.';
        $tipoMensaje = 'error';
    } else {
        $idTelaEliminarInt = (int) $idTelaEliminar;
        $stmtEliminarTela = mysqli_prepare($conexion, 'DELETE FROM telas WHERE Id = ?');

        if ($stmtEliminarTela) {
            mysqli_stmt_bind_param($stmtEliminarTela, 'i', $idTelaEliminarInt);
            $eliminacionCorrecta = mysqli_stmt_execute($stmtEliminarTela);
            $filasEliminadas = $eliminacionCorrecta ? mysqli_stmt_affected_rows($stmtEliminarTela) : 0;
            mysqli_stmt_close($stmtEliminarTela);

            if ($eliminacionCorrecta && $filasEliminadas > 0) {
                $mensaje = 'Articulo eliminado correctamente.';
                $tipoMensaje = 'ok';
            } else {
                $mensaje = 'No se pudo eliminar el articulo seleccionado.';
                $tipoMensaje = 'error';
            }
        } else {
            $mensaje = 'Ocurrio un error al preparar la eliminacion del articulo.';
            $tipoMensaje = 'error';
        }
    }
}

$articulosDisponibles = [];
$resultadoArticulos = mysqli_query($conexion, "SELECT DISTINCT articulo FROM telas WHERE articulo IS NOT NULL AND TRIM(articulo) <> '' ORDER BY articulo");

if ($resultadoArticulos) {
    while ($filaArticulo = mysqli_fetch_assoc($resultadoArticulos)) {
        $articulosDisponibles[] = trim((string) ($filaArticulo['articulo'] ?? ''));
    }
    mysqli_free_result($resultadoArticulos);
}

if ($articuloBuscado !== '') {
    $stmtTelas = mysqli_prepare($conexion, "SELECT Id, articulo, muestrario, composicion, pero, rango, pagina, foto FROM telas WHERE articulo LIKE CONCAT('%', ?, '%') ORDER BY articulo, muestrario, rango, Id");
    if ($stmtTelas) {
        mysqli_stmt_bind_param($stmtTelas, 's', $articuloBuscado);
        mysqli_stmt_execute($stmtTelas);
        $result = mysqli_stmt_get_result($stmtTelas);
    } else {
        $result = false;
    }
} else {
    $result = mysqli_query($conexion, "SELECT Id, articulo, muestrario, composicion, pero, rango, pagina, foto FROM telas ORDER BY articulo, muestrario, rango, Id");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Telas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            margin: 0;
            min-height: 100dvh;
            padding: clamp(10px, 2.4vw, 20px);
            overflow-x: hidden;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .barra-superior {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .barra-superior a {
            flex: 1 1 140px;
            max-width: 170px;
            text-align: center;
            padding: 10px 12px;
            background: #1e5f74;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        .barra-superior a:hover {
            background: #17495a;
        }
        .boton-buscar {
            flex: 1 1 140px;
            max-width: 170px;
            padding: 10px 12px;
            border: 0;
            background: #1e5f74;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .boton-buscar:hover {
            background: #17495a;
        }
        .buscador-articulo {
            display: none;
            margin: 0 auto 20px;
            padding: 14px;
            border: 1px solid #d8e2e8;
            border-radius: 10px;
            background: #f7fafb;
        }
        .buscador-articulo.abierto {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            gap: 10px;
        }
        .buscador-articulo label {
            display: flex;
            flex: 1 1 260px;
            flex-direction: column;
            gap: 6px;
            color: #2c3e50;
            font-weight: 600;
        }
        .buscador-articulo input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #b9cbd4;
            border-radius: 8px;
            font-size: 15px;
        }
        .boton-ejecutar-busqueda,
        .limpiar-busqueda {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }
        .boton-ejecutar-busqueda {
            border: 0;
            background: #1e5f74;
            color: #ffffff;
        }
        .limpiar-busqueda {
            border: 1px solid #b9cbd4;
            background: #ffffff;
            color: #2c3e50;
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
        .boton-eliminar {
            padding: 5px 8px;
            border: 0;
            border-radius: 6px;
            background: #b91c1c;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .boton-eliminar:hover,
        .boton-eliminar:focus-visible {
            background: #991b1b;
        }
        .presentacion {
            margin-bottom: 24px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            background: #f2f2f2;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .presentacion img {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            object-fit: contain;
        }
        .table-wrap {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px 12px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background: #1e5f74;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
        }
        img {
            max-width: 120px;
            border-radius: 8px;
            display: block;
        }
        .mini-foto-tela {
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

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 14px;
                border-radius: 10px;
            }
            .presentacion {
                margin-bottom: 16px;
                border-radius: 10px;
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .presentacion img {
                width: 95%;
                max-width: 95%;
                height: auto;
                max-height: 360px;
                object-fit: contain;
                margin: 0 auto;
                display: block;
            }
            table, thead, tbody, th, td, tr {
                display: block;
                width: 100%;
            }
            thead {
                display: none;
            }
            tbody tr {
                margin-bottom: 12px;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background: #fff;
                width: 100%;
                box-sizing: border-box;
            }
            td {
                border: none;
                border-bottom: 1px solid #f0f0f0;
                padding: 8px 0;
            }
            td:last-child {
                border-bottom: none;
            }
            td::before {
                content: attr(data-label);
                font-weight: bold;
                color: #1e5f74;
                display: inline-block;
                min-width: 95px;
                margin-right: 8px;
            }
            img {
                max-width: 100%;
                width: 100%;
                max-height: 220px;
                object-fit: cover;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Catálogo de Telas</h1>
        <div class="barra-superior">
            <a href="inicio.php?v=<?php echo urlencode((string) time()); ?>">Volver a inicio</a>
            <button type="button" id="botonMostrarBusqueda" class="boton-buscar" aria-expanded="<?php echo $articuloBuscado !== '' ? 'true' : 'false'; ?>">Buscar</button>
            <a href="agregar_tela.php">Agregar tela</a>
        </div>
        <form id="buscadorArticulo" class="buscador-articulo<?php echo $articuloBuscado !== '' ? ' abierto' : ''; ?>" method="get" action="telas.php">
            <label for="articulo">Artículo
                <input type="search" id="articulo" name="articulo" value="<?= htmlspecialchars($articuloBuscado, ENT_QUOTES, 'UTF-8') ?>" list="articulosDisponibles" placeholder="Escriba o seleccione un artículo" autocomplete="off">
                <datalist id="articulosDisponibles">
                    <?php foreach ($articulosDisponibles as $articuloDisponible): ?>
                        <option value="<?= htmlspecialchars($articuloDisponible, ENT_QUOTES, 'UTF-8') ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </label>
            <button type="submit" class="boton-ejecutar-busqueda">Buscar artículo</button>
            <a class="limpiar-busqueda" href="telas.php">Limpiar</a>
        </form>
        <?php if ($mensaje !== ''): ?>
            <p class="mensaje <?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th>Muestrario</th>
                            <th>Composición</th>
                            <th>Peso</th>
                            <th>Rango</th>
                            <th>Página</th>
                            <th>Foto</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td data-label="Artículo"><?= htmlspecialchars($fila['articulo'] ?? 'Sin artículo') ?></td>
                                <td data-label="Muestrario"><?= htmlspecialchars($fila['muestrario'] ?? '-') ?></td>
                                <td data-label="Composición"><?= htmlspecialchars($fila['composicion'] ?? '-') ?></td>
                                <td data-label="Peso"><?= htmlspecialchars($fila['pero'] ?? '-') ?></td>
                                <td data-label="Rango"><?= htmlspecialchars($fila['rango'] ?? '-') ?></td>
                                <td data-label="Página"><?= htmlspecialchars($fila['pagina'] ?? '-') ?></td>
                                <td data-label="Foto">
                                    <?php
                                    $fotoValor = trim((string) ($fila['foto'] ?? ''));
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

                                    <?php if ($rutaFoto !== ''): ?>
                                        <img class="mini-foto-tela" src="<?= htmlspecialchars($rutaFoto) ?>" alt="Foto de la tela">
                                    <?php endif; ?>
                                </td>
                                <td data-label="Acción">
                                    <form method="post" action="telas.php" onsubmit="return confirm('¿Desea eliminar este artículo?');">
                                        <input type="hidden" name="id_tela" value="<?= (int) ($fila['Id'] ?? 0) ?>">
                                        <input type="hidden" name="articulo" value="<?= htmlspecialchars($articuloBuscado, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" name="eliminar_tela" class="boton-eliminar">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty">No se encontraron registros en la base de datos.</div>
        <?php endif; ?>

    </div>

    <div id="modalImagenTela" class="modal-imagen" aria-hidden="true">
        <div class="modal-imagen-contenido">
            <button type="button" id="cerrarModalImagenTela" class="modal-imagen-cerrar" aria-label="Cerrar imagen ampliada">×</button>
            <img id="imagenTelaAmpliada" class="modal-imagen-foto" src="" alt="Imagen ampliada de tela">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var botonMostrarBusqueda = document.getElementById('botonMostrarBusqueda');
            var buscadorArticulo = document.getElementById('buscadorArticulo');
            var campoArticulo = document.getElementById('articulo');
            var modalImagenTela = document.getElementById('modalImagenTela');
            var imagenTelaAmpliada = document.getElementById('imagenTelaAmpliada');
            var cerrarModalImagenTela = document.getElementById('cerrarModalImagenTela');

            if (botonMostrarBusqueda && buscadorArticulo) {
                botonMostrarBusqueda.addEventListener('click', function () {
                    var estaAbierto = buscadorArticulo.classList.toggle('abierto');
                    botonMostrarBusqueda.setAttribute('aria-expanded', estaAbierto ? 'true' : 'false');

                    if (estaAbierto && campoArticulo) {
                        campoArticulo.focus();
                    }
                });
            }

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
                    var rutaImagen = imagen.getAttribute('src') || '';
                    var textoAlternativo = imagen.getAttribute('alt') || 'Imagen ampliada de tela';

                    if (!modalImagenTela || !imagenTelaAmpliada || rutaImagen === '') {
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
        });
    </script>
</body>
</html>
