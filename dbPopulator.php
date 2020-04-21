<?php
$dbName = $_GET['dbName'];
$max = $_GET['max'];

$mysqli = @new mysqli("127.0.0.1", "root", "", $dbName);
$values = $mysqli->query("SHOW TABLES FROM "  . $dbName);
$tables = [];

foreach ($values as $value) {
    array_push($tables, $value['Tables_in_' . $dbName]);
}

foreach ($tables as $table) {
    $attributes = $mysqli->query("SHOW COLUMNS FROM " . $table);
    echo "INSERT INTO " . $table . " (";
    $first = true;
    foreach ($attributes as $attribute) {
        if ($first) {
            echo $attribute['Field'] . ', ';
            $first = false;
        } else
            echo ", " . $attribute['Field'];
    }
    echo ") VALUES <br />";
    for ($i = 0; $i < $max; $i++) {
        echo "(";
        $first = true;
        foreach ($attributes as $attribute) {
            if ($first)
                $first = false;
            else
                echo ", ";

            $pathPos = stripos($attribute['Field'] , "path");
            if ($pathPos !== false)
                echo "'/path/to/" . substr($attribute['Field'], $pathPos + 4) . $i . "'";
            elseif (stripos($attribute['Type'], "char") !== false)
                echo "'" . $attribute['Field'] . $i . "'" ;
            elseif (stripos($attribute['Type'], "int") !== false)
                echo $i;
            elseif (stripos($attribute['Type'], "decimal") !== false 
                || stripos($attribute['Type'], "float") !== false)
                echo $i . "..5";
            elseif (stripos($attribute['Type'], "enum") !== false) {
                $type = preg_replace("/enum|\(|\)|\'/", "", $attribute['Type']);
                $enum = explode(',', $type);
                if (isset($enum[$i]))
                    echo "'" . $enum[$i] . "'";
                else
                    echo "'" . $enum[0] . "'" ;
            } elseif (stripos($attribute['Type'], "date") !== false) {
                echo "'" . date("Y-m-d") . "'" ;
            } elseif (stripos($attribute['Type'], "year") !== false) {
                echo "'" . date("Y") . "'" ;
            } elseif (stripos($attribute['Type'], "text") !== false) {
                echo "'someTExT'" ;
            }
        }
        if ($i == $max - 1)
            echo ");<br /><br />";
        else
            echo "),<br />";
    }
}
?>
