<?php
/*  24-08-2026  desde PC
archivo: telas.php
Descripcion: Muestra un catálogo de telas, permite buscar por artículo y eliminar registros.
*/
require_once 'conexion.php';

$articuloBuscado = trim($_GET['articulo'] ?? '');
$mensaje = '';
$tipoMensaje = '';

if (isset($_GET['eliminado']) && $_GET['eliminado'] === '1') {
    $mensaje = 'Articulo eliminado correctamente.';
    $tipoMensaje = 'ok';
}

if (isset($_GET['actualizado']) && $_GET['actualizado'] === '1') {
    $mensaje = 'Articulo actualizado correctamente.';
    $tipoMensaje = 'ok';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_tela'])) {
    $idTelaActualizar = trim($_POST['id_tela'] ?? '');
    $articuloBuscado = trim($_POST['articulo_busqueda'] ?? '');
    $articulo = trim($_POST['articulo_editar'] ?? '');
    $muestrario = trim($_POST['muestrario_editar'] ?? '');
    $composicion = trim($_POST['composicion_editar'] ?? '');
    $peso = trim($_POST['peso_editar'] ?? '');
    $rango = trim($_POST['rango_editar'] ?? '');
    $pagina = trim($_POST['pagina_editar'] ?? '');
    $foto = trim($_POST['foto_editar'] ?? '');

    if (!ctype_digit($idTelaActualizar) || (int) $idTelaActualizar <= 0) {
        $mensaje = 'No se pudo actualizar: identificador de tela invalido.';
        $tipoMensaje = 'error';
    } elseif ($articulo === '' || $muestrario === '' || $composicion === '' || $peso === '' || $rango === '' || $pagina === '') {
        $mensaje = 'Complete todos los campos antes de guardar.';
        $tipoMensaje = 'error';
    } elseif (!ctype_digit($peso) || !ctype_digit($rango)) {
        $mensaje = 'Peso y Rango deben ser valores numericos enteros.';
        $tipoMensaje = 'error';
    } else {
        $stmtActualizarTela = mysqli_prepare($conexion, 'UPDATE telas SET articulo = ?, muestrario = ?, composicion = ?, pero = ?, rango = ?, pagina = ?, foto = ? WHERE Id = ?');

        if ($stmtActualizarTela) {
            $idTelaActualizarInt = (int) $idTelaActualizar;
            $pesoEntero = (int) $peso;
            $rangoEntero = (int) $rango;
            mysqli_stmt_bind_param($stmtActualizarTela, 'sssiissi', $articulo, $muestrario, $composicion, $pesoEntero, $rangoEntero, $pagina, $foto, $idTelaActualizarInt);
            $actualizacionCorrecta = mysqli_stmt_execute($stmtActualizarTela);
            $errorActualizacion = mysqli_stmt_error($stmtActualizarTela);
            mysqli_stmt_close($stmtActualizarTela);

            if ($actualizacionCorrecta) {
                $parametrosRedireccion = ['actualizado' => '1'];
                if ($articuloBuscado !== '') {
                    $parametrosRedireccion['articulo'] = $articuloBuscado;
                }

                header('Location: telas.php?' . http_build_query($parametrosRedireccion));
                exit;
            }

            $mensaje = 'No se pudo actualizar el articulo: ' . ($errorActualizacion !== '' ? $errorActualizacion : 'error desconocido.');
            $tipoMensaje = 'error';
        } else {
            $mensaje = 'Ocurrio un error al preparar la actualizacion del articulo.';
            $tipoMensaje = 'error';
        }
    }
}

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
            $errorEliminacion = mysqli_stmt_error($stmtEliminarTela);
            mysqli_stmt_close($stmtEliminarTela);

            if ($eliminacionCorrecta && $filasEliminadas > 0) {
                $parametrosRedireccion = ['eliminado' => '1'];
                if ($articuloBuscado !== '') {
                    $parametrosRedireccion['articulo'] = $articuloBuscado;
                }

                header('Location: telas.php?' . http_build_query($parametrosRedireccion));
                exit;
            } elseif (!$eliminacionCorrecta && $errorEliminacion !== '') {
                $mensaje = 'No se pudo eliminar el articulo: ' . $errorEliminacion;
                $tipoMensaje = 'error';
            } else {
                $parametrosRedireccion = [];
                if ($articuloBuscado !== '') {
                    $parametrosRedireccion['articulo'] = $articuloBuscado;
                }

                $urlRedireccion = 'telas.php';
                if ($parametrosRedireccion !== []) {
                    $urlRedireccion .= '?' . http_build_query($parametrosRedireccion);
                }

                header('Location: ' . $urlRedireccion);
                exit;
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
    $stmtTelas = mysqli_prepare($conexion, "SELECT Id, articulo, muestrario, composicion, pero, rango, pagina, foto FROM telas WHERE articulo LIKE CONCAT('%', ?, '%') ORDER BY muestrario, CAST(SUBSTRING_INDEX(pagina, '(', 1) AS UNSIGNED), articulo, rango, Id");
    if ($stmtTelas) {
        mysqli_stmt_bind_param($stmtTelas, 's', $articuloBuscado);
        mysqli_stmt_execute($stmtTelas);
        $result = mysqli_stmt_get_result($stmtTelas);
    } else {
        $result = false;
    }
} else {
    $result = mysqli_query($conexion, "SELECT Id, articulo, muestrario, composicion, pero, rango, pagina, foto FROM telas ORDER BY muestrario, CAST(SUBSTRING_INDEX(pagina, '(', 1) AS UNSIGNED), articulo, rango, Id");
}

$telasPorMuestrario = [];
$hayRegistrosTelas = false;

if ($result) {
    while ($filaTela = mysqli_fetch_assoc($result)) {
        $hayRegistrosTelas = true;
        $nombreMuestrario = trim((string) ($filaTela['muestrario'] ?? ''));
        if ($nombreMuestrario === '') {
            $nombreMuestrario = 'Sin muestrario';
        } elseif (stripos($nombreMuestrario, 'IL GUARDAROBA ULTIMATE') !== false) {
            $nombreMuestrario = 'IL GUARDAROBA ULTIMATE';
        }

        if (!array_key_exists($nombreMuestrario, $telasPorMuestrario)) {
            $telasPorMuestrario[$nombreMuestrario] = [];
        }

        $telasPorMuestrario[$nombreMuestrario][] = $filaTela;
    }
}

if (isset($stmtTelas) && $stmtTelas) {
    mysqli_stmt_close($stmtTelas);
}

if ($result instanceof mysqli_result) {
    mysqli_free_result($result);
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
        .logo-brioni {
            position: absolute;
            top: 12px;
            right: 14px;
            width: clamp(80px, 12vw, 140px) !important;
            height: auto;
            max-height: none !important;
            z-index: 5;
            border-radius: 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
            background: #ffffff;
        }
        .container {
            position: relative;
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
        .acciones-tela {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .acciones-tela form {
            margin: 0;
        }
        .boton-editar,
        .boton-guardar-edicion,
        .boton-cancelar-edicion {
            padding: 5px 8px;
            border: 0;
            border-radius: 6px;
            color: #ffffff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .boton-editar {
            background: #2563eb;
        }
        .boton-editar:hover,
        .boton-editar:focus-visible {
            background: #1d4ed8;
        }
        .boton-guardar-edicion {
            display: none;
            background: #15803d;
        }
        .boton-guardar-edicion:hover,
        .boton-guardar-edicion:focus-visible {
            background: #166534;
        }
        .boton-cancelar-edicion {
            display: none;
            background: #64748b;
        }
        .boton-cancelar-edicion:hover,
        .boton-cancelar-edicion:focus-visible {
            background: #475569;
        }
        .fila-editando .boton-editar,
        .fila-editando .boton-eliminar {
            display: none;
        }
        .fila-editando .boton-guardar-edicion,
        .fila-editando .boton-cancelar-edicion {
            display: inline-block;
        }
        .campo-edicion {
            display: none;
            width: 100%;
            box-sizing: border-box;
            padding: 6px 8px;
            border: 1px solid #9bb6c3;
            border-radius: 6px;
            font: inherit;
        }
        .fila-editando .valor-tela {
            display: none;
        }
        .fila-editando .campo-edicion {
            display: block;
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
        .bloque-tabla {
            margin-bottom: 10px;
            border: 1px solid #d8e2e8;
            border-radius: 10px;
            overflow: hidden;
            background: #ffffff;
        }
        .bloque-tabla:last-child {
            margin-bottom: 0;
        }
        .acordeon-linea {
            width: 100%;
            border: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 14px;
            background: #eef5f8;
            color: #17495a;
            font-size: 1rem;
            font-weight: 700;
            text-align: left;
            cursor: pointer;
        }
        .acordeon-linea:hover,
        .acordeon-linea:focus-visible {
            background: #e2edf2;
        }
        .acordeon-flecha {
            font-size: 0.85rem;
            transform: rotate(0deg);
            transition: transform 0.2s ease;
            flex: 0 0 auto;
        }
        .acordeon-conteo {
            font-size: 0.85rem;
            color: #4b6070;
            font-weight: 600;
        }
        .tabla-wrapper {
            display: none;
            overflow-x: auto;
            border-top: 1px solid #d8e2e8;
        }
        .bloque-tabla.abierta .tabla-wrapper {
            display: block;
        }
        .bloque-tabla.abierta .acordeon-flecha {
            transform: rotate(180deg);
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
            .logo-brioni {
                top: 8px;
                right: 8px;
                width: clamp(64px, 20vw, 96px) !important;
            }
            body {
                padding: 10px;
            }
            .container {
                padding: 14px;
                padding-top: 30px;
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
        <img class="logo-brioni" src="fotos/brioni.jpg" alt="Logo Brioni">
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
        <?php if ($hayRegistrosTelas): ?>
            <?php foreach ($telasPorMuestrario as $nombreMuestrario => $filasMuestrario): ?>
                <section class="bloque-tabla">
                    <button type="button" class="acordeon-linea" aria-expanded="false">
                        <span><?php echo htmlspecialchars('Muestrario: ' . $nombreMuestrario, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="acordeon-conteo"><?php echo (int) count($filasMuestrario); ?> artículo(s)</span>
                        <span class="acordeon-flecha">▼</span>
                    </button>

                    <div class="tabla-wrapper">
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
                                <?php foreach ($filasMuestrario as $fila): ?>
                                    <?php $idTela = (int) ($fila['Id'] ?? 0); ?>
                                    <tr>
                                        <td data-label="Artículo">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['articulo'] ?? 'Sin artículo') ?></span>
                                            <input class="campo-edicion" type="text" name="articulo_editar" value="<?= htmlspecialchars($fila['articulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
                                        <td data-label="Muestrario">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['muestrario'] ?? '-') ?></span>
                                            <input class="campo-edicion" type="text" name="muestrario_editar" value="<?= htmlspecialchars($fila['muestrario'] ?? '', ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
                                        <td data-label="Composición">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['composicion'] ?? '-') ?></span>
                                            <input class="campo-edicion" type="text" name="composicion_editar" value="<?= htmlspecialchars($fila['composicion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
                                        <td data-label="Peso">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['pero'] ?? '-') ?></span>
                                            <input class="campo-edicion" type="number" min="0" name="peso_editar" value="<?= htmlspecialchars((string) ($fila['pero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
                                        <td data-label="Rango">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['rango'] ?? '-') ?></span>
                                            <input class="campo-edicion" type="number" min="0" name="rango_editar" value="<?= htmlspecialchars((string) ($fila['rango'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
                                        <td data-label="Página">
                                            <span class="valor-tela"><?= htmlspecialchars($fila['pagina'] ?? '-') ?></span>
                                            <input class="campo-edicion" type="text" name="pagina_editar" value="<?= htmlspecialchars($fila['pagina'] ?? '', ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" required>
                                        </td>
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
                                            <input class="campo-edicion" type="text" name="foto_editar" value="<?= htmlspecialchars($fotoValor, ENT_QUOTES, 'UTF-8') ?>" form="editarTela<?= $idTela ?>" placeholder="Nombre del archivo">
                                        </td>
                                        <td data-label="Acción">
                                            <div class="acciones-tela">
                                                <form id="editarTela<?= $idTela ?>" method="post" action="telas.php">
                                                    <input type="hidden" name="id_tela" value="<?= $idTela ?>">
                                                    <input type="hidden" name="articulo_busqueda" value="<?= htmlspecialchars($articuloBuscado, ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" name="actualizar_tela" class="boton-guardar-edicion">Guardar</button>
                                                </form>
                                                <button type="button" class="boton-editar">Editar</button>
                                                <button type="button" class="boton-cancelar-edicion">Cancelar</button>
                                                <form method="post" action="telas.php" onsubmit="return confirm('¿Desea eliminar este artículo?');">
                                                    <input type="hidden" name="id_tela" value="<?= $idTela ?>">
                                                    <input type="hidden" name="articulo" value="<?= htmlspecialchars($articuloBuscado, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" name="eliminar_tela" class="boton-eliminar">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>
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

            document.querySelectorAll('.bloque-tabla').forEach(function (bloque) {
                var botonAcordeon = bloque.querySelector('.acordeon-linea');
                if (!botonAcordeon) {
                    return;
                }

                botonAcordeon.addEventListener('click', function () {
                    var estaAbierto = bloque.classList.contains('abierta');

                    document.querySelectorAll('.bloque-tabla').forEach(function (otroBloque) {
                        var otroBoton = otroBloque.querySelector('.acordeon-linea');
                        otroBloque.classList.remove('abierta');
                        if (otroBoton) {
                            otroBoton.setAttribute('aria-expanded', 'false');
                        }
                    });

                    if (!estaAbierto) {
                        bloque.classList.add('abierta');
                        botonAcordeon.setAttribute('aria-expanded', 'true');
                    }
                });
            });

            document.querySelectorAll('tbody tr').forEach(function (fila) {
                var botonEditar = fila.querySelector('.boton-editar');
                var botonCancelar = fila.querySelector('.boton-cancelar-edicion');
                var formularioEdicion = fila.querySelector('form[id^="editarTela"]');

                if (botonEditar) {
                    botonEditar.addEventListener('click', function () {
                        fila.classList.add('fila-editando');
                        var primerCampo = fila.querySelector('.campo-edicion');
                        if (primerCampo) {
                            primerCampo.focus();
                        }
                    });
                }

                if (botonCancelar) {
                    botonCancelar.addEventListener('click', function () {
                        if (formularioEdicion) {
                            formularioEdicion.reset();
                        }
                        fila.classList.remove('fila-editando');
                    });
                }
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
