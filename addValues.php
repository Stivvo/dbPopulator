<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>DB populator</title>
        <link rel="stylesheet" href="style.css" type="text/css" media="all">
    </head>
    <body>
        <h1>Insert default values</h1>
<?php
$dbName = $_GET['dbName'];
$mysqli = @new mysqli("127.0.0.1", "root", "", $dbName);
$max = $_GET['numRows'];
?>
        <form id="" action="dbPopulator.php" method="GET">
            <label for="dbName">Database name</label>
            <input type="text" name="dbName" value="<?php echo $dbName; ?>" readonly >
            <label for="numRows">Number of rows</label>
            <input type="number" name="numRows" value="<?php echo $max; ?>" readonly >
            <p>
                For each attribute, a range of custom values can be optionally specified.
                If none is given, default values will be used.
            </p><p>
                For example, default values for an attribute named "City": 'City0', 'City1'...
            </p><p>
                Custom values must be comma separated, quotes are automatically inserted for string data types.
            </p><p>
                If the number of rows is greater than the number of inserted custom values for a specific attribute, they will be repeated in the same order to match the specified number of rows, unless "not repeat values" is checked. This will fill the remaining rows with default values
            </p>
<?php
require "getDbData.php";

foreach ($tables as $table) {
    $attributes = $mysqli->query("SHOW COLUMNS FROM " . $table);
    echo "<hr /><h2>" . $table . "</h2>";
    foreach ($attributes as $attribute) {
        if (empty($attribute['Key'])) { ?>
        <h3><?php echo $attribute['Field']; ?></h3>
        <div>
            <input type="checkbox" name="<?php echo "STOP" . $attribute['Field'] ?>" value="true">
            <label for="<?php echo "STOP" . $attribute['Field'] ?>">not repeat values</label>
        </div>
<textarea name="<?php echo $attribute['Field'] ?>" rows="4" cols="80" placeholder="Value1, Value2, Value3">
</textarea>
<?php
        }
    }
}
?>
            <div>
                <hr />
                <input type="submit" value="populate database">
            </div>
        </form>
    </body>
</html>
