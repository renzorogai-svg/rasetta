<?php
/** 26-08-2026 desde laptop
 * reportes.php
 *
 * Página de reportes de ventas.
 *
 * @package Administrador
 */
require_once __DIR__ . '/conexion.php';

$pedidos = [];
$resultadoPedidos = mysqli_query(
    $conexion,
    "SELECT usuario, nombre, pedido, fecha
     FROM cliente
     GROUP BY usuario, nombre, pedido, fecha
     ORDER BY fecha DESC, pedido DESC, nombre ASC"
);

if ($resultadoPedidos) {
    while ($fila = mysqli_fetch_assoc($resultadoPedidos)) {
        $pedidos[] = $fila;
    }
    mysqli_free_result($resultadoPedidos);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de ventas</title>
    <style>
        :root {
            --fondo: #e2e8f0;
            --panel: #ffffff;
            --encabezado: #1e293b;
            --borde: #cbd5e1;
            --texto: #1e293b;
            --acento: #0284c7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            padding: 24px;
            background: var(--fondo);
            color: var(--texto);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .contenedor {
            width: min(900px, 100%);
            margin: 0 auto;
            padding: 24px;
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.14);
        }

        .encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 20px;
        }

        h1 {
            margin: 0;
            font-size: 1.5rem;
        }

        .boton-volver {
            padding: 8px 12px;
            color: #ffffff;
            background: var(--acento);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(15, 23, 42, 0.2);
        }

        .boton-volver:hover,
        .boton-volver:focus-visible {
            background: #0369a1;
        }

        .tabla-contenedor {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid var(--borde);
            text-align: left;
        }

        th {
            padding: 11px 12px;
        }

        th {
            color: #ffffff;
            background: var(--encabezado);
            font-weight: 600;
        }

        tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        tbody tr:hover {
            background: #e0f2fe;
        }

        .enlace-fila {
            display: block;
            padding: 11px 12px;
            color: inherit;
            text-decoration: none;
        }

        .enlace-fila:focus-visible {
            outline: 2px solid var(--acento);
            outline-offset: -2px;
        }

        .sin-pedidos {
            padding: 18px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .contenedor {
                padding: 16px;
            }

            .encabezado {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <main class="contenedor">
        <div class="encabezado">
            <h1>Reporte de ventas</h1>
            <a class="boton-volver" href="inicio.php">Volver</a>
        </div>

        <?php if (!empty($pedidos)): ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha del pedido</th>
                            <th>Nombre del cliente</th>
                            <th>Numero del pedido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <?php $enlacePedido = 'ver_cliente.php?' . http_build_query(['usuario' => $pedido['usuario'] ?? '', 'pedido' => $pedido['pedido'] ?? '']); ?>
                                <td><a class="enlace-fila" href="<?php echo htmlspecialchars($enlacePedido, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(date('d/m/Y', strtotime((string) ($pedido['fecha'] ?? ''))), ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><a class="enlace-fila" href="<?php echo htmlspecialchars($enlacePedido, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($pedido['nombre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
                                <td><a class="enlace-fila" href="<?php echo htmlspecialchars($enlacePedido, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($pedido['pedido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="sin-pedidos">No hay pedidos registrados.</p>
        <?php endif; ?>
    </main>
</body>
</html>
