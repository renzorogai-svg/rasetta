<?php
/* 20-08-2026  desde PC
    Archivo: agregar_cliente.php
  
*/ 
require_once __DIR__ . '/conexion.php';

$nombre = '';
$usuario = '';
$idCliente = '';
$telefono = '';
$direccion = '';
$correo = '';
$mensaje = '';
$tipoMensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $idCliente = trim($_POST['id_cliente'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    if ($nombre === '' || $usuario === '' || $idCliente === '' || $telefono === '' || $direccion === '' || $correo === '') {
        $mensaje = 'Complete todos los campos.';
        $tipoMensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Ingrese un correo electronico valido.';
        $tipoMensaje = 'error';
    } else {
        $pedido = 1;
        $producto = '0';
        $precio = 0;
        $fecha = date('Y-m-d');

        $sql = 'INSERT INTO cliente (`ID cliente`, nombre, usuario, telefono, direccion, correo, pedido, fecha, producto, precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssssssissi', $idCliente, $nombre, $usuario, $telefono, $direccion, $correo, $pedido, $fecha, $producto, $precio);
            $ok = mysqli_stmt_execute($stmt);

            if ($ok) {
                mysqli_stmt_close($stmt);
                // Redirige a la pantalla de inicio tras guardar con éxito
                header('Location: inicio.php?v=' . urlencode((string) time()));
                exit;
            } else {
                $mensaje = 'No se pudo guardar el cliente.';
                $tipoMensaje = 'error';
            }

            mysqli_stmt_close($stmt);
        } else {
            $mensaje = 'Error al preparar la consulta.';
            $tipoMensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar cliente</title>
    <style>
        :root {
            --fondo: #f2f6fb;
            --panel: #ffffff;
            --texto: #1f2937;
            --borde: #d6dee8;
            --acento: #0f766e;
            --acento-hover: #0b5f59;
            --sombra: 0 12px 28px rgba(15, 23, 42, 0.12);
            --error: #b91c1c;
            --ok: #166534;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #e9eef9;
            color: var(--texto);
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .contenedor {
            width: min(620px, 100%);
            background: var(--panel);
            border: 1px solid var(--borde);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--sombra);
        }

        h1 {
            margin: 0 0 18px;
            font-size: 1.55rem;
        }

        .formulario {
            display: grid;
            gap: 12px;
        }

        .campo {
            display: grid;
            gap: 6px;
        }

        label {
            font-weight: 600;
            font-size: 0.95rem;
        }

        input {
            padding: 10px;
            border: 1px solid var(--borde);
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .boton {
            display: inline-block;
            text-decoration: none;
            border: 0;
            cursor: pointer;
            background: var(--acento);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.92rem;
            padding: 9px 14px;
            border-radius: 10px;
            transition: background 0.2s ease;
        }

        .boton:hover,
        .boton:focus-visible {
            background: var(--acento-hover);
        }

        .boton-secundario {
            background: #334155;
        }

        .boton-secundario:hover,
        .boton-secundario:focus-visible {
            background: #1e293b;
        }

        .mensaje {
            margin: 0 0 12px;
            font-weight: 700;
        }

        .mensaje.error {
            color: var(--error);
        }

        .mensaje.ok {
            color: var(--ok);
        }

        @media (max-width: 640px) {
            .contenedor {
                padding: 18px;
                border-radius: 12px;
            }
        }
    </style>
</head>
<body>
    <main class="contenedor">
        <h1>Agregar cliente</h1>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje <?php echo htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form class="formulario" method="post" action="">
            <div class="campo">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" maxlength="40" required>
            </div>

            <div class="campo">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8'); ?>" maxlength="30" required>
            </div>

            <div class="campo">
                <label for="id_cliente">ID cliente</label>
                <input type="text" id="id_cliente" name="id_cliente" value="<?php echo htmlspecialchars($idCliente, ENT_QUOTES, 'UTF-8'); ?>" maxlength="20" required>
            </div>

            <div class="campo">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>" maxlength="40" required>
            </div>

            <div class="campo">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($direccion, ENT_QUOTES, 'UTF-8'); ?>" maxlength="200" required>
            </div>

            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>" maxlength="150" required>
            </div>

            <div class="acciones">
                <button class="boton" type="submit">Aceptar</button>
                <a class="boton boton-secundario" href="inicio.php?v=<?php echo urlencode((string) time()); ?>">Volver</a>
            </div>
        </form>
    </main>
</body>
</html>