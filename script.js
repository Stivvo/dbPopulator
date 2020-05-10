function showTable(tableName) {
    var table = document.getElementById(tableName);
    if (table.style.visibility == "hidden") {
        table.style.visibility = "visible";
        table.style.width = "auto";
        table.style.height = "auto";
    } else {
        table.style.visibility = "hidden";
        table.style.width = "0px";
        table.style.height = "0px";
    }
}
