<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width">
        <title>DB populator</title>
        <link rel="stylesheet" href="style.css" type="text/css" media="all">
    </head>
    <body>
        <form id="" action="dbPopulator.php" method="GET">
            <div>
                <label for="dbName">Database Name</label>
                <select name="dbName">
<?php
$mysqli = @new mysqli("127.0.0.1", "root", "", $dbName);
$values = $mysqli->query("SHOW DATABASES");

foreach ($values as $value) {
    $value = $value['Database'];?>
    <option value="<?php echo $value; ?>"><?php echo $value; ?></option><?php
}

$mysqli->close();
?>

                </select>
            </div>
            <div>
                <label for="max">Number of lines per table</label>
                <input type="number" name="max" value="">
            </div>
            <input type="submit" value="generate populator">
        </form>
    </body>
</html>
