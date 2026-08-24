<?php
require_once __DIR__ . '/conexion.php';

$usuarioOriginal = trim($_POST['usuario_original'] ?? $_GET['usuario'] ?? '');
$nombre = '';
$usuario = $usuarioOriginal;
$idCliente = '';
$telefono = '';
$direccion = '';
$correo = '';
$mensaje = '';
$tipoMensaje = '';

if ($usuarioOriginal !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmtCliente = mysqli_prepare($conexion, 'SELECT `ID cliente`, nombre, usuario, telefono, direccion, correo FROM cliente WHERE usuario = ? ORDER BY Id ASC LIMIT 1');
    if ($stmtCliente) {
        mysqli_stmt_bind_param($stmtCliente, 's', $usuarioOriginal);
        mysqli_stmt_execute($stmtCliente);
        $resultadoCliente = mysqli_stmt_get_result($stmtCliente);
        $cliente = $resultadoCliente ? mysqli_fetch_assoc($resultadoCliente) : null;
        mysqli_stmt_close($stmtCliente);

        if (is_array($cliente)) {
            $nombre = (string) ($cliente['nombre'] ?? '');
            $usuario = (string) ($cliente['usuario'] ?? $usuarioOriginal);
            $idCliente = (string) ($cliente['ID cliente'] ?? '');
            $telefono = (string) ($cliente['telefono'] ?? '');
            $direccion = (string) ($cliente['direccion'] ?? '');
            $correo = (string) ($cliente['correo'] ?? '');
        } else {
            $mensaje = 'No se encontro el cliente.';
            $tipoMensaje = 'error';
        }
    } else {
        $mensaje = 'No se pudo preparar la consulta.';
        $tipoMensaje = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $idCliente = trim($_POST['id_cliente'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    $longitudesValidas = strlen($nombre) <= 40 && strlen($usuario) <= 30 && strlen($idCliente) <= 20
        && strlen($telefono) <= 40 && strlen($direccion) <= 200 && strlen($correo) <= 150;

    if ($usuarioOriginal === '' || $nombre === '' || $usuario === '' || $idCliente === '' || $telefono === '' || $direccion === '' || $correo === '') {
        $mensaje = 'Complete todos los campos.';
        $tipoMensaje = 'error';
    } elseif (!$longitudesValidas) {
        $mensaje = 'Uno de los campos supera la longitud permitida.';
        $tipoMensaje = 'error';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'Ingrese un correo electronico valido.';
        $tipoMensaje = 'error';
    } else {
        $stmtExiste = mysqli_prepare($conexion, 'SELECT 1 FROM cliente WHERE usuario = ? LIMIT 1');
        $usuarioExiste = false;
        if ($stmtExiste) {
            mysqli_stmt_bind_param($stmtExiste, 's', $usuario);
            mysqli_stmt_execute($stmtExiste);
            $resultadoExiste = mysqli_stmt_get_result($stmtExiste);
            $usuarioExiste = $usuario !== $usuarioOriginal && $resultadoExiste && mysqli_num_rows($resultadoExiste) > 0;
            mysqli_stmt_close($stmtExiste);
        }

        if ($usuarioExiste) {
            $mensaje = 'El nuevo usuario ya existe.';
            $tipoMensaje = 'error';
        } else {
            mysqli_begin_transaction($conexion);
            $sqlUpdate = 'UPDATE cliente SET `ID cliente` = ?, nombre = ?, usuario = ?, telefono = ?, direccion = ?, correo = ? WHERE usuario = ?';
            $stmtUpdate = mysqli_prepare($conexion, $sqlUpdate);

            if ($stmtUpdate) {
                mysqli_stmt_bind_param($stmtUpdate, 'sssssss', $idCliente, $nombre, $usuario, $telefono, $direccion, $correo, $usuarioOriginal);
                $okUpdate = mysqli_stmt_execute($stmtUpdate);
                mysqli_stmt_close($stmtUpdate);

                if ($okUpdate) {
                    mysqli_commit($conexion);
                    header('Location: ver_cliente.php?' . http_build_query(['usuario' => $usuario, 'mensaje' => 'Cliente actualizado']));
                    exit;
                }

                mysqli_rollback($conexion);
            }

            $mensaje = 'No se pudo actualizar el cliente.';
            $tipoMensaje = 'error';
        }
    }
}

function escapar($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar cliente</title>
    <style>
        :root {
            --texto: #1f2937;
            --borde: #d6dee8;
            --acento: #0f766e;
            --acento-hover: #0b5f59;
            --fondo: #e9eef9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: start center;
            padding: 20px;
            background: var(--fondo);
            color: var(--texto);
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .tarjeta {
            width: min(760px, 100%);
            padding: 24px;
            background: #ffffff;
            border: 1px solid var(--borde);
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        h1 {
            margin: 0 0 20px;
            font-size: 1.4rem;
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .campo-ancho {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--borde);
            border-radius: 7px;
            font: inherit;
        }

        .mensaje {
            margin: 0 0 14px;
            font-weight: 600;
            color: #b91c1c;
        }

        .acciones {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .boton {
            display: inline-block;
            padding: 8px 12px;
            border: 0;
            border-radius: 8px;
            background: var(--acento);
            color: #ffffff;
            font: inherit;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
        }

        .boton:hover,
        .boton:focus-visible {
            background: var(--acento-hover);
        }

        @media (max-width: 640px) {
            .tarjeta {
                padding: 18px;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo-ancho {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <main class="tarjeta">
        <h1>Editar cliente</h1>

        <?php if ($mensaje !== ''): ?>
            <p class="mensaje"><?php echo escapar($mensaje); ?></p>
        <?php endif; ?>

        <?php if ($usuarioOriginal !== ''): ?>
            <form class="formulario" method="post" action="editar_cliente.php">
                <input type="hidden" name="usuario_original" value="<?php echo escapar($usuarioOriginal); ?>">

                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo escapar($nombre); ?>" maxlength="40" required>
                </div>

                <div class="campo">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" value="<?php echo escapar($usuario); ?>" maxlength="30" required>
                </div>

                <div class="campo">
                    <label for="id_cliente">ID cliente</label>
                    <input type="text" id="id_cliente" name="id_cliente" value="<?php echo escapar($idCliente); ?>" maxlength="20" required>
                </div>

                <div class="campo">
                    <label for="telefono">Telefono</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo escapar($telefono); ?>" maxlength="40" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="direccion">Direccion</label>
                    <input type="text" id="direccion" name="direccion" value="<?php echo escapar($direccion); ?>" maxlength="200" required>
                </div>

                <div class="campo campo-ancho">
                    <label for="correo">Correo</label>
                    <input type="email" id="correo" name="correo" value="<?php echo escapar($correo); ?>" maxlength="150" required>
                </div>

                <div class="acciones campo-ancho">
                    <button class="boton" type="submit">Guardar cambios</button>
                    <a class="boton" href="ver_cliente.php?usuario=<?php echo urlencode($usuarioOriginal); ?>">Cancelar</a>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
