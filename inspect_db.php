<?php
$mysqli = new mysqli('localhost', 'root', '', 'rasetta');
if ($mysqli->connect_error) {
    die('ERROR: ' . $mysqli->connect_error);
}

echo "TABLAS:\n";
$result = $mysqli->query('SHOW TABLES');
while ($row = $result->fetch_array()) {
    echo '- ' . $row[0] . "\n";
}

$tables = [];
$result = $mysqli->query('SHOW TABLES');
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

echo "\nCOLUMNAS:\n";
foreach ($tables as $table) {
    echo "Tabla: $table\n";
    $cols = $mysqli->query("SHOW COLUMNS FROM `$table`");
    while ($col = $cols->fetch_assoc()) {
        echo ' - ' . $col['Field'] . ' (' . $col['Type'] . ')\n';
    }
    echo "\n";
}
