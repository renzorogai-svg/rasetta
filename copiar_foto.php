<?php
$mysqli = new mysqli('localhost', 'root', '', 'rasetta');
if ($mysqli->connect_error) {
    die($mysqli->connect_error);
}

$result = $mysqli->query('SELECT Id, articulo FROM telas');
while ($row = $result->fetch_assoc()) {
    $foto = $row['articulo'];
    $id = (int) $row['Id'];
    $fotoEscapada = $mysqli->real_escape_string($foto);
    $mysqli->query("UPDATE telas SET foto = '$fotoEscapada' WHERE Id = $id");
}

echo "Listo";
