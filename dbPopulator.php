<?php
if ($_GET['downMethod'] == "show") {
    $break = "<br />";
?>
    <!DOCTYPE html>
    <html>
    <head>
        <link rel="stylesheet" href="style.css" type="text/css">
    </head>
    <body id="sqlCode">
<?php
} else {
    $break = "\n";
    header('Content-type: text/sql');
    header("Content-disposition: filename=output.sql");
}

?>
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

$fileName = "/tmp/dbPopulatorOutput.sql";
$file = fopen($fileName, "w+");
fclose($file);
$file = fopen($fileName, "a");

function out($str) {
    echo $str;
    fwrite($GLOBALS['file'], $str);
}

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
    out("INSERT INTO " . $table . " (");
    $first = true;
    foreach ($attributes as $attribute) { // print attributes
        if (stripos($attribute['Extra'], "AUTO_INCREMENT") === false) {
            if ($first) {
                out($attribute['Field']);
                $first = false;
            } else
                out(", " . $attribute['Field']);
        }
    }
    out(") VALUES" . $break);

    $getNumber = $_GET['NUMBER/' . $table];
    if (empty($getNumber))
        $max = $numRows;
    else
        $max = $getNumber;

    for ($i = 0; $i < $max; $i++) { // rows
        out("(");
        $first = true;
        foreach ($attributes as $attr) { // columns
            $pos = -1;
            $elemPos = -1;
            $j = 0;
            while ($pos == -1 && $j < count($fks)) { // is FK?
                $tempPos = array_search($attr['Field'], $fks[$j]['column_']);
                if ($tempPos !== false) { // is definetly FK
                    $pos = $j;
                    $elemPos = $tempPos;
                }
                $j++;
            }
            if ($pos == -1) // is NOT FK
                $attribute = $attr;
            else { // get the name of the referenced PK
                $primary = mysqli_fetch_all($mysqli->query(
                    "SHOW KEYS FROM " . $fks[$pos]['primary_'] . " WHERE Key_name = 'PRIMARY'"
                ), MYSQLI_ASSOC);

                /* out("attributo: " . $attr['Field'] . ", entità lato uno: " . $fks[$pos]['primary_']); */
                /* out("column name: " . $primary[$elemPos]['Column_name']); */
                /* out("SHOW COLUMNS FROM " . $fks[$pos]['primary_'] . " WHERE Field = '" . $primary[$elemPos]['Column_name'] . "'"); */

                $attribute = mysqli_fetch_all($mysqli->query(
                    "SHOW COLUMNS FROM " . $fks[$pos]['primary_'] . " WHERE Field = '" . $primary[$elemPos]['Column_name'] . "'"
                ), MYSQLI_ASSOC)[$elemPos];
                /* print_r($attribute); */
            }

            $goCustom = false;
            $j = $i;

            $getEntity = "ENTITY/" . $table . "/" . $attribute['Field'];
            $getStop = "STOP/" . $table . "/" . $attribute['Field'];
            if (!empty($_GET[$getEntity])) { // some custom values specified
                $customValues = explode(', ', $_GET[$getEntity]); // get custom values
                $goCustom = true;
                if (isset($_GET[$getStop]) && !empty($_GET[$getStop])) { // not repeat
                    if ($i >= count($customValues)) { // custom value finished
                        $goCustom = false;
                        $j = $i - count($customValues);
                    }
                }
            }
            $type = $attribute['Type'];
            $field = $attribute['Field'];

            if (stripos($attr['Extra'], "AUTO_INCREMENT") === false) {
                if ($first)
                    $first = false;
                else
                    out(", ");
            }

            if ($goCustom) { // use custom values for this row of this column (attribute)
                $outStr = $customValues[$i % count($customValues)];

                if (!(stripos($type, "dec") !== false
                    || stripos($type, "float") !== false
                    || stripos($type, "double") !== false
                    || stripos($type, "int") !== false))
                    $outStr = "'" . $outStr . "'";
                out($outStr);
            } elseif (stripos($attr['Extra'], "AUTO_INCREMENT") === false) { // no custom values
                // don't insert auto_increment attributes
                // attr is checked instead of attribute because if attr is a FK, attribute will get the name of the referenced PK

                $sizePos = stripos($type, "(");
                $decPos = stripos($type, ",");
                $endPos = stripos($type, ")");
                $pathPos = stripos($field, "path");
                $size = intval(substr($type, $sizePos + 1, $endPos - $sizePos));

                if ($j == 0) {
                    $nrDigits = 1;
                } else {
                    $nrDigits = 0;
                    $tmpJ = $j;
                    while ($tmpJ >= 1) {
                        $tmpJ /= 10;
                        $nrDigits++;
                    }
                    $nrDigits = intval($nrDigits);
                }

                if ($pathPos !== false)
                    out("'/path/to/" . substr($field, $pathPos + 4) . $i . "'");
                elseif (stripos($type, "var") !== false) {
                    if (strlen($field) + $nrDigits > $size)
                        $cut = substr($field, $size - $nrDigits);
                    else
                        $cut = $field;
                    out("'" . $cut . $j . "'" );
                }
                elseif (stripos($type, "char") !== false) {
                    out("'" . str_repeat(chr(($j % 26) + 65), intval(substr($type, $sizePos + 1, $endPos - $sizePos)) - $nrDigits) . $j . "'");

                } elseif (stripos($attribute['Extra'], "AUTO_INCREMENT") !== false) { // auto_increment FK
                    $referencedPk = "";
                    $k = 0;
                    while (empty($referencedPk) && $k < count($fks)) {
                        if ($fks[$k]['column_'][0] == $attr['Field'])
                            $referencedPk = $fks[$k]['primary_'];
                        $k++;
                    }
                    $getNumber = $_GET["NUMBER/" . $referencedPk];
                    $autoIncrement = mysqli_fetch_all($mysqli->query("SHOW TABLE STATUS LIKE '" . $table . "'"), MYSQLI_ASSOC)[0]['Auto_increment'];

                    if (!isset($autoIncrement) || empty($autoIncrement))
                        $autoIncrement = 1;
                    out(($autoIncrement + $j) % ((empty($getNumber) ? $numRows : $getNumber) + $autoIncrement));
                } elseif (stripos($type, "int") !== false
                    || stripos($type, "bit") !== false) {
                        out($j % pow(10, $size));
                } elseif (stripos($type, "dec") !== false
                    || stripos($type, "double") !== false
                    || stripos($type, "float") !== false) {
                        $size = intval(substr($type, $sizePos + 1, $decPos - $sizePos - 1)); // size is only the integer part
                        $dec = intval(substr($type, $decPos + 1, $endPos - $decPos)); // dec is the size of the decimal part
                        out(($j % pow(10, $size)) . "." . ($j % pow(10, $dec)));
                } elseif (stripos($type, "enum") !== false) {
                    $type = preg_replace("/enum|\(|\)|\'/", "", $type);
                    $enum = explode(',', $type);
                    out("'" . $enum[$j % count($enum)] . "'");
                } elseif (stripos($type, "date") !== false) {
                    out("'" . date("Y-m-d") . "'" );
                } elseif (stripos($type, "time") !== false) {
                    out("'" . date("h:i:s") . "'" );
                } elseif (stripos($type, "year") !== false) {
                    out("'" . date("Y") . "'" );
                } elseif (stripos($type, "text") !== false) {
                    out("'someTExT'" );
                } else
                    out("unknown datatype");
            }
        }
        if ($i == $max - 1)
            out(");" . $break . $break);
        else
            out(")," . $break);
    }
}
$mysqli->close();
fclose($file);

if ($_GET['downMethod'] == "show") { ?>
    </body>
</html>
<?php } ?>
