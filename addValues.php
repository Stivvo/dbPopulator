<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <script charset="utf-8" src="script.js"></script>
    <title>DB populator</title>
    <link rel="stylesheet" href="style.css" type="text/css" media="all">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"></link>
</head>
<body>
    <h1>Insert custom values</h1>
<?php
$dbName = $_GET['dbName'];
$mysqli = @new mysqli("127.0.0.1", "root", "", $dbName);
$max = $_GET['numRows'];
?>
    <form action="dbPopulator.php" method="GET">
        <label for="dbName">database name</label>
        <input type="text" name="dbName" value="<?php echo $dbName; ?>" readonly >
        <label for="numRows">global number of rows</label>
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
        </p><p>
            Primary keys are forced to not repeat custom values. Custom values can't be set at all for auto_increment primary keys, enums and foreign keys
        </p><p>
            A custom number of rows can be specified for each entity. if it is not, the global number of rows will be used instead
        </p><p>
            Foreign keys that are also primary keys are only detected as primary keys, so you should set custom values to fix that
        </p>
<?php
require "getDbData.php";

foreach ($tables as $table) {
    $attributes = $mysqli->query("SHOW COLUMNS FROM " . $table);
    $getNumber = "NUMBER/" . $table;
    $getContent = "CONTENT/" . $table; ?>
    <hr />
    <div class="table" onclick="showTable('<?php echo $table ?>')">
        <h2>
        <span id="<?php echo "FA/" . $table ?>"></span>
<?php echo $table ?>
        </h2>
    </div>
    <div id="<?php echo $getContent ?>" class="tableContent">
        <input type="number" name="<?php echo $getNumber ?>">
        <label for="<?php echo $getNumber ?>">number of rows</label>
    <?php
    foreach ($attributes as $attribute) {
        if ($attribute['Key'] != "MUL" && $attribute['Extra'] != "auto_increment" && stripos($attribute['Type'], "enum") === false) { ?>
            <h3>
<?php echo (empty($attribute['Key']) ? "<span class=\"fa fa-key\"></span>&nbsp;" : "") . $attribute['Field']; ?>
            </h3>
            <div>
        <?php   $getStop = "STOP/" . $table . "/" . $attribute['Field'];

                if (empty($attribute['Key'])) { ?>
                    <input type="checkbox" name="<?php echo $getStop ?>" value="true">
        <?php   } else { // is primary key ?>
                    <input type="checkbox" name="dummy" disabled="true" checked="checked">
                    <input type="checkbox" name="<?php echo $getStop ?>" value="true"
                        style="display: none" checked="checked">
        <?php   } ?>
                <label for="<?php echo $getStop ?>">not repeat values</label>
            </div>
        <textarea name="<?php echo "ENTITY/" . $table . "/" . $attribute['Field'] ?>" rows="4" cols="80" placeholder="Value1, Value2, Value3"></textarea>
<?php   }
    } ?>
    <script>
        showTable('<?php echo $table ?>');
    </script>
    </div>
<?php
}
$mysqli->close(); ?>
        <div>
            <hr />
            <input type="submit" value="populate database">
        </div>
    </form>
</body>
</html>
