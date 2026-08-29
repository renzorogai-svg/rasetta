<?php
/** 28-08-2026 desde PC
 * inicio.php
 *
 * Página de inicio del administrador.
 *
 * Muestra opciones para gestionar productos, telas y clientes.
 *
 * @package Administrador
 */
require_once __DIR__ . '/conexion.php';

// Evita que el navegador reutilice una version en cache de la lista de clientes.
$marcaTemporal = gmdate('D, d M Y H:i:s') . ' GMT';
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0, no-transform');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Surrogate-Control: no-store');
header('X-Accel-Expires: 0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . $marcaTemporal);
header('ETag: "inicio-' . md5($marcaTemporal . microtime(true)) . '"');

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

$stmtClientes = mysqli_prepare($conexion, "SELECT DISTINCT usuario, nombre, `ID cliente`, telefono, direccion, correo FROM cliente WHERE TRIM(usuario) <> '' ORDER BY nombre ASC, usuario ASC");

if ($stmtClientes) {
    mysqli_stmt_execute($stmtClientes);
    $resultadoClientes = mysqli_stmt_get_result($stmtClientes);

    if ($resultadoClientes) {
        while ($cliente = mysqli_fetch_assoc($resultadoClientes)) {
            $clientesDisponibles[] = [
                'usuario' => (string) ($cliente['usuario'] ?? ''),
                'nombre' => (string) ($cliente['nombre'] ?? ''),
                'id_cliente' => trim((string) ($cliente['ID cliente'] ?? '')) ?: 'vacio',
                'telefono' => trim((string) ($cliente['telefono'] ?? '')) ?: 'vacio',
                'direccion' => trim((string) ($cliente['direccion'] ?? '')) ?: 'vacio',
                'correo' => trim((string) ($cliente['correo'] ?? '')) ?: 'vacio'
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
    <title>Panel Administrador</title>
    <style>
        :root {
            --fondo: #1e293b;
            --panel-header: #0f172a;
            --panel-bg: rgba(255, 255, 255, 0.95);
            --texto: #1e293b;
            --borde: #cbd5e1;
            --acento: #0284c7;
            --acento-hover: #0369a1;
            --sombra: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        }

        * {
            box-sizing: border-box;
        }

        body {
            position: relative;
            margin: 0;
            height: 100vh;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: #e2e8f0;
            color: var(--texto);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: url("fotos/fondo.jpg?v=<?php echo htmlspecialchars($versionFondo, ENT_QUOTES, 'UTF-8'); ?>") center center / cover no-repeat;
            z-index: -2;
            filter: brightness(0.85);
        }

        /* BARRA SUPERIOR TIPO WINDOWS / APP */
        .app-header {
            background: var(--panel-header);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 52px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            position: relative;
        }

        .app-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .app-title-icon {
            width: 12px;
            height: 12px;
            background: #38bdf8;
            border-radius: 2px;
        }

        .toolbar-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }

        .btn-tool {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.88rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.15);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-tool:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: #ffffff;
        }

        .btn-tool-primary {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .btn-tool-primary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
        }

        /* CONTENEDOR DESPLEGABLE EN LA BARRA DE HERRAMIENTAS */
        .menu-buscar-wrapper {
            position: relative;
        }

        .dropdown-clientes {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 320px;
            background: #ffffff;
            border: 1px solid var(--borde);
            border-radius: 10px;
            box-shadow: var(--sombra);
            padding: 10px;
            z-index: 2000;
        }

        .dropdown-clientes.activo {
            display: block !important;
        }

        .campo-filtro-top {
            width: 100%;
            padding: 6px;
            border: 1px solid var(--borde);
            border-radius: 6px;
            font-size: 0.9rem;
            outline: none;
            margin-bottom: 10px;
        }

        .campo-filtro-top:focus {
            border-color: var(--acento);
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
        }

        .lista-clientes {
            max-height: 260px;
            overflow-y: auto;
            border: 1px solid #f1f5f9;
            border-radius: 6px;
        }

        .item-cliente {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px;
            text-decoration: none;
            color: var(--texto);
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        .item-cliente:last-child {
            border-bottom: none;
        }

        .item-cliente:hover {
            background: #f0f9ff;
        }

        .nombre-cliente {
            font-weight: 600;
            color: #0f172a;
        }

        .usuario-cliente {
            font-size: 0.8rem;
            color: #64748b;
        }

        .sin-resultados {
            padding: 12px;
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
        }

        /* ÁREA PRINCIPAL LIMPIA */
        .app-workspace {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }

        .bloqueo-acceso {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(15, 23, 42, 0.78);
        }

        .dialogo-acceso {
            width: min(100%, 360px);
            padding: 28px;
            background: #ffffff;
            border: 1px solid var(--borde);
            border-radius: 10px;
            box-shadow: var(--sombra);
            text-align: center;
        }

        .dialogo-acceso h1 {
            margin: 0 0 8px;
            font-size: 1.35rem;
        }

        .dialogo-acceso p {
            margin: 0 0 18px;
            color: #64748b;
        }

        .campo-clave-acceso {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borde);
            border-radius: 6px;
            font: inherit;
            text-align: center;
        }

        .campo-clave-acceso:focus {
            border-color: var(--acento);
            outline: 2px solid rgba(2, 132, 199, 0.2);
        }

        .boton-acceso {
            width: 100%;
            margin-top: 14px;
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            background: var(--acento);
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .boton-acceso:hover {
            background: var(--acento-hover);
        }

        .error-acceso {
            min-height: 20px;
            margin: 10px 0 0;
            color: #b91c1c;
            font-size: 0.88rem;
        }

        @media (min-width: 768px) {
            .btn-tool {
                padding: 5px 10px;
                font-size: 0.82rem;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.28);
            }
        }

        /* RESPONSIVO PARA DISPOSITIVOS MÓVILES */
        @media (max-width: 767px) {
            body {
                height: auto;
                overflow-y: auto;
            }

            .app-header {
                flex-direction: column;
                height: auto;
                padding: 12px;
                gap: 12px;
            }

            .toolbar-menu {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .dropdown-clientes {
                left: 50%;
                transform: translateX(-50%);
                width: 290px;
            }
        }
    </style>
</head>
<body>

    <div class="bloqueo-acceso" id="bloqueo-acceso">
        <form class="dialogo-acceso" id="formulario-acceso">
            <h1>Acceso requerido</h1>
            <p>Introduzca la clave para continuar.</p>
            <input class="campo-clave-acceso" id="clave-acceso" type="password" value="" autocomplete="off" aria-label="Clave de acceso" autofocus>
            <button class="boton-acceso" type="submit">Confirmar</button>
            <p class="error-acceso" id="error-acceso" role="alert" aria-live="polite"></p>
        </form>
    </div>

    <!-- BARRA SUPERIOR CON MENÚ Y BÚSQUEDA DESPLEGABLE -->
    <header class="app-header">
        <div class="app-title">
            <span class="app-title-icon"></span>
            Panel de Administración
        </div>
        <nav class="toolbar-menu">
            <div class="menu-buscar-wrapper">
                <button type="button" class="btn-tool" id="btn-top-buscar">🔍 Buscar cliente</button>
                
                <!-- DESPLEGABLE CON LISTA DE CLIENTES -->
                <div class="dropdown-clientes" id="dropdown-clientes">
                    <input type="text" id="filtro-cliente" class="campo-filtro-top" placeholder="Escriba para filtrar..." autocomplete="off">
                    <div class="lista-clientes" id="lista-clientes">
                        <?php if (!empty($clientesDisponibles)): ?>
                            <?php foreach ($clientesDisponibles as $cliente): ?>
                                <?php $textoCliente = trim($cliente['nombre'] ?? '') !== '' ? $cliente['nombre'] : $cliente['usuario']; ?>
                                <a class="item-cliente" href="ver_cliente.php?usuario=<?php echo urlencode($cliente['usuario']); ?>" data-busqueda="<?php echo htmlspecialchars(strtolower($textoCliente . ' ' . $cliente['usuario']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="nombre-cliente"><?php echo htmlspecialchars($textoCliente, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="usuario-cliente"><?php echo htmlspecialchars($cliente['usuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="sin-resultados">No hay clientes registrados</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <a class="btn-tool btn-tool-primary" href="agrega_cliente.php">+ Agregar cliente</a>
            <a class="btn-tool" href="listini.php">Lista precios</a>
            <a class="btn-tool" href="telas.php">Muestrario telas</a>
            <a class="btn-tool" href="reportes.php">Reporte ventas</a>
            <a class="btn-tool" href="backup.php">Backup</a>
        </nav>
    </header>

    <!-- ÁREA PRINCIPAL -->
    <main class="app-workspace">
        <!-- ÁREA DE TRABAJO LIMPIA -->
    </main>

    <script>
        (function () {
            const bloqueo = document.getElementById('bloqueo-acceso');
            const formulario = document.getElementById('formulario-acceso');
            const clave = document.getElementById('clave-acceso');
            const error = document.getElementById('error-acceso');
            const claveCorrecta = 'rasetta';
            const navegacion = performance.getEntriesByType('navigation')[0];
            const esRecarga = navegacion && navegacion.type === 'reload';
            const accesoConcedido = sessionStorage.getItem('rasettaAccesoConcedido') === 'true';

            if (accesoConcedido && !esRecarga) {
                bloqueo.remove();
                return;
            }

            if (esRecarga) {
                sessionStorage.removeItem('rasettaAccesoConcedido');
            }

            document.body.style.overflow = 'hidden';

            formulario.addEventListener('submit', function (event) {
                event.preventDefault();

                if (clave.value === claveCorrecta) {
                    sessionStorage.setItem('rasettaAccesoConcedido', 'true');
                    bloqueo.remove();
                    document.body.style.overflow = '';
                    return;
                }

                error.textContent = 'La clave no es correcta.';
                clave.select();
            });

            bloqueo.addEventListener('click', function (event) {
                if (event.target === bloqueo) {
                    clave.focus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (document.body.contains(bloqueo) && event.key === 'Escape') {
                    event.preventDefault();
                    clave.focus();
                }
            });
        })();

        (function () {
            const queryActual = window.location.search;
            const tieneTokenManual = /^\?v\d+$/i.test(queryActual);

            if (!tieneTokenManual) {
                const token = 'v' + Date.now().toString() + Math.floor(Math.random() * 100000).toString();
                window.location.replace('inicio.php?' + token);
            }
        })();

        document.addEventListener('DOMContentLoaded', function () {
            const btnBuscar = document.getElementById('btn-top-buscar');
            const dropdown = document.getElementById('dropdown-clientes');
            const filtro = document.getElementById('filtro-cliente');
            const items = dropdown ? dropdown.querySelectorAll('.item-cliente') : [];

            if (!btnBuscar || !dropdown) {
                return;
            }

            // Alternar la visibilidad de la lista de clientes
            btnBuscar.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('activo');
                if (dropdown.classList.contains('activo') && filtro) {
                    filtro.focus();
                }
            });

            // Evitar que al hacer clic dentro del desplegable se cierre
            dropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            // Filtrado de clientes en tiempo real
            if (filtro) {
                filtro.addEventListener('input', function () {
                    const valor = filtro.value.trim().toLowerCase();
                    items.forEach(function (item) {
                        const textoBusqueda = item.getAttribute('data-busqueda') || '';
                        if (valor === '' || textoBusqueda.includes(valor)) {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            }

            // Cerrar el desplegable si se hace clic fuera
            document.addEventListener('click', function () {
                dropdown.classList.remove('activo');
            });
        });

    </script>
</body>
</html>