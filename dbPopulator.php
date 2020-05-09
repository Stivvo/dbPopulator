<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>DB populator</title>
        <link rel="stylesheet" href="style.css" type="text/css" media="all">
    </head>
    <body>
<?php
$dbName = $_GET['dbName'];
$numRows = $_GET['numRows'];

$mysqli = @new mysqli("127.0.0.1", "root", "", $dbName);
require "getDbData.php";

$fksRaw = $mysqli->query("
select fks.table_name as foreign_,
       fks.referenced_table_name as primary_,
       group_concat(kcu.column_name
            order by position_in_unique_constraint separator ', ') as column_
from information_schema.referential_constraints fks
join information_schema.key_column_usage kcu
     on fks.constraint_schema = kcu.table_schema
     and fks.table_name = kcu.table_name
     and fks.constraint_name = kcu.constraint_name
where fks.constraint_schema = '" . $dbName . "'
group by fks.constraint_schema,
         fks.table_name,
         fks.unique_constraint_schema,
         fks.referenced_table_name,
         fks.constraint_name
");

$fks = [];
$i = 0;
foreach ($fksRaw as $value) {
    $fks[$i]['foreign_'] = $value['foreign_'];
    $fks[$i]['primary_'] = $value['primary_'];
    $fks[$i]['column_'] = explode(',', $value['column_']);
    $i++;
}
/* print_r($fks); */

foreach ($tables as $table) {
    $attributes = $mysqli->query("SHOW COLUMNS FROM " . $table);
    echo "INSERT INTO " . $table . " (";
    $first = true;
    foreach ($attributes as $attribute) { // print attributes
        if (stripos($attribute['Extra'], "AUTO_INCREMENT") === false) {
            if ($first) {
                echo $attribute['Field'];
                $first = false;
            } else
                echo ", " . $attribute['Field'];
        }
    }
    echo ") VALUES <br />";

    if (empty($_GET['NUMBER' . $table]))
        $max = $numRows;
    else
        $max = $_GET['NUMBER' . $table];

    for ($i = 0; $i < $max; $i++) {
        echo "(";
        $first = true;
        foreach ($attributes as $attr) {

            $pos = -1;
            $elemPos = -1;
            $j = 0;
            while ($pos == -1 && $j < count($fks)) {
                /* echo $attr['Field']; */
                $tempPos = array_search($attr['Field'], $fks[$j]['column_']);
                if ($tempPos !== false) {
                    $pos = $j;
                    $elemPos = $tempPos;
                }
                $j++;
            }
            if ($pos == -1)
                $attribute = $attr;
            else {
                $primary = mysqli_fetch_all($mysqli->query(
                    "SHOW KEYS FROM " . $fks[$pos]['primary_'] . " WHERE Key_name = 'PRIMARY'"
                ), MYSQLI_ASSOC);

                /* echo "attributo: " . $attr['Field'] . ", entità lato uno: " . $fks[$pos]['primary_']; */
                /* echo "column name: " . $primary[$elemPos]['Column_name']; */
                /* echo "SHOW COLUMNS FROM " . $fks[$pos]['primary_'] . " WHERE Field = '" . $primary[$elemPos]['Column_name'] . "'"; */

                $attribute = mysqli_fetch_all($mysqli->query(
                    "SHOW COLUMNS FROM " . $fks[$pos]['primary_'] . " WHERE Field = '" . $primary[$elemPos]['Column_name'] . "'"
                ), MYSQLI_ASSOC)[$elemPos];
                /* print_r($attribute); */
            }

            if ($first)
                $first = false;
            else
                echo ", ";

            $goCustom = false;
            $j = $i;

            if (!empty($_GET[$attribute['Field']])) {
                $customValues = explode(', ', $_GET[$attribute['Field']]); // we actually want custom values
                $goCustom = true;
                if (isset($_GET['STOP' . $attribute['Field']]) && !empty($_GET['STOP' . $attribute['Field']])) { // not repeat
                    if ($i >= count($customValues)) { // custom value finished
                        $goCustom = false;
                        $j = $i - count($customValues);
                    }
                }
            }

            if ($goCustom) {
                $outStr = $customValues[$i % count($customValues)]; // custom values can be used

                if (!(stripos($attribute['Type'], "dec") !== false
                    || stripos($attribute['Type'], "float") !== false
                    || stripos($attribute['Type'], "double") !== false
                    || stripos($attribute['Type'], "int") !== false))
                    $outStr = "'" . $outStr . "'";
                echo $outStr;
            } elseif (stripos($attribute['Extra'], "AUTO_INCREMENT") === false) { // no custom values
                // don't insert auto_increment attributes
                $sizePos = stripos($attribute['Type'], "(");
                $decPos = stripos($attribute['Type'], ",");
                $endPos = stripos($attribute['Type'], ")");
                $pathPos = stripos($attribute['Field'], "path");
                $size = intval(substr($attribute['Type'], $sizePos + 1, $endPos - $sizePos));

                if ($pathPos !== false)
                    echo "'/path/to/" . substr($attribute['Field'], $pathPos + 4) . $i . "'";
                elseif (stripos($attribute['Type'], "var") !== false) {
                    if (strlen($attribute['Field']) >= $size)
                        $cut = substr($attribute['Field'], $size - 1);
                    else
                        $cut = $attribute['Field'];
                    echo "'" . $cut . $j . "'" ;

                }
                elseif (stripos($attribute['Type'], "char") !== false)
                    echo "'" . str_repeat(chr($j + 65), intval(substr($attribute['Type'], $sizePos + 1, $endPos - $sizePos))) . "'";
                elseif (stripos($attribute['Type'], "dec") !== false
                    || stripos($attribute['Type'], "int") !== false
                    || stripos($attribute['Type'], "bit") !== false
                    || stripos($attribute['Type'], "double") !== false
                    || stripos($attribute['Type'], "float") !== false) {
                    if ($decPos === false) {
                        $dec = -1; // size is already set
                    } else {
                        $size = intval(substr($attribute['Type'], $sizePos + 1, $decPos - $sizePos - 1));
                        $dec = intval(substr($attribute['Type'], $decPos + 1, $endPos - $decPos));
                    }

                    if ($dec != -1) {
                        $dec = intval($dec);
                        echo str_repeat($j, $size - $dec) . "." . str_repeat($j, $dec);
                    } else
                        echo str_repeat($j, $size);

                }
                elseif (stripos($attribute['Type'], "enum") !== false) {
                    $type = preg_replace("/enum|\(|\)|\'/", "", $attribute['Type']);
                    $enum = explode(',', $type);
                    echo "'" . $enum[$j % count($enum)] . "'";
                } elseif (stripos($attribute['Type'], "date") !== false) {
                    echo "'" . date("Y-m-d") . "'" ;
                } elseif (stripos($attribute['Type'], "time") !== false) {
                    echo "'" . date("h:i:s") . "'" ;
                } elseif (stripos($attribute['Type'], "year") !== false) {
                    echo "'" . date("Y") . "'" ;
                } elseif (stripos($attribute['Type'], "text") !== false) {
                    echo "'someTExT'" ;
                } else
                    echo "unknown datatype";
            } else
                $first = true;
        }
        if ($i == $max - 1)
            echo ");<br /><br />";
        else
            echo "),<br />";
    }
}
$mysqli->close();
?>
    </body>
</html>
