<?php //04-08-2026
$user = "root";
$pass = "";
$server = "localhost";
$db = "rasetta";
$conexion = mysqli_connect($server, $user, $pass, $db);

if (!$conexion) {
    die('No se pudo conectar a la base de datos: ' . mysqli_connect_error());
}

mysqli_set_charset($conexion, 'utf8');
