<?php
require_once 'conexion.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {
    $archivo = $_FILES['excel']['tmp_name'];
    $nombre = $_FILES['excel']['name'];

    if (pathinfo($nombre, PATHINFO_EXTENSION) !== 'xlsx') {
        $message = 'Solo se permiten archivos .xlsx';
        $messageType = 'error';
    } else {
        require_once 'vendor/autoload.php';

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray();

        $insertados = 0;

        foreach ($filas as $index => $fila) {
            if ($index === 0) continue;
            if (empty($fila[0]) && empty($fila[1]) && empty($fila[2])) continue;

            $articulo = mysqli_real_escape_string($conexion, trim((string)($fila[0] ?? '')));
            $muestrario = mysqli_real_escape_string($conexion, trim((string)($fila[1] ?? '')));
            $composicion = mysqli_real_escape_string($conexion, trim((string)($fila[2] ?? '')));
            $pero = (int)($fila[3] ?? 0);
            $rango = (int)($fila[4] ?? 0);
            $pagina = mysqli_real_escape_string($conexion, trim((string)($fila[5] ?? '')));
            $foto = mysqli_real_escape_string($conexion, trim((string)($fila[6] ?? '')));

            $sql = "INSERT INTO telas (articulo, muestrario, composicion, pero, rango, pagina, foto) VALUES ('$articulo', '$muestrario', '$composicion', $pero, $rango, '$pagina', '$foto')";
            if (mysqli_query($conexion, $sql)) {
                $insertados++;
            }
        }

        $message = "Se importaron $insertados filas correctamente.";
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar telas desde Excel</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .box { max-width: 600px; margin: auto; background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .msg { padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .success { background: #e8f5e9; color: #2e7d32; }
        .error { background: #ffebee; color: #c62828; }
        input[type=file] { margin: 10px 0; }
        button { background: #1e5f74; color: white; padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Importar telas desde Excel</h2>
        <p>Sube un archivo .xlsx con las columnas: artículo, muestrario, composición, pero, rango, página y foto.</p>

        <?php if ($message !== ''): ?>
            <div class="msg <?= $messageType === 'error' ? 'error' : 'success' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="excel" accept=".xlsx" required>
            <br>
            <button type="submit">Importar</button>
        </form>

        <p style="margin-top: 15px;"><a href="telas.php">Volver al catálogo</a></p>
    </div>
</body>
</html>
