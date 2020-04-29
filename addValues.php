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
        <input type="text" name="dbName" value="<?php echo $dbName; ?>" readonly >
        <input type="number" name="numRows" value="<?php echo $max; ?>" readonly >
<?php
require "getDbData.php";

foreach ($tables as $table) {
    $attributes = $mysqli->query("SHOW COLUMNS FROM " . $table);
    echo "<hr /><h2>" . $table . "</h2>";
    foreach ($attributes as $attribute) {
        if (empty($attribute['Key'])) { ?>
<h3><?php echo $attribute['Field']; ?></h3>
<textarea name="<?php echo $attribute['Field'] ?>" rows="4" cols="80"></textarea>
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
