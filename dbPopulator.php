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
$max = $_GET['numRows'];

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
    foreach ($attributes as $attribute) {
        if ($first) {
            echo $attribute['Field'];
            $first = false;
        } else
            echo ", " . $attribute['Field'];
    }
    echo ") VALUES <br />";
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

            if (!empty($_GET[$attribute['Field']])) {
                $customValues = explode(', ', $_GET[$attribute['Field']]); // we actually want custom values
                $goCustom = true;

                if (!empty($_GET['STOP' . $attribute['Field']])) { // not repeat
                    if ($i >= count($customValues))
                        $goCustom = false;
                }
            }

            if ($goCustom) {
                $outStr = $customValues[$i % count($customValues)]; // custom values can be used

                if (!(stripos($attribute['Type'], "decimal") !== false 
                    || stripos($attribute['Type'], "float") !== false
                    || stripos($attribute['Type'], "int") !== false))
                    $outStr = "'" . $outStr . "'";
                echo $outStr;
            } else {
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
                } elseif (stripos($attribute['Type'], "time") !== false) {
                    echo "'" . date("h:i:s") . "'" ;
                } elseif (stripos($attribute['Type'], "year") !== false) {
                    echo "'" . date("Y") . "'" ;
                } elseif (stripos($attribute['Type'], "text") !== false) {
                    echo "'someTExT'" ;
                } else
                    echo "unknown datatype";
            }

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
