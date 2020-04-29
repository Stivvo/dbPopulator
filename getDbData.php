<?php

$values = $mysqli->query("SHOW TABLES FROM "  . $dbName);
$tables = [];

foreach ($values as $value) {
    array_push($tables, $value['Tables_in_' . $dbName]);
}

?>
