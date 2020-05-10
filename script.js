function showTable(table) {
    var tableContent = document.getElementById("CONTENT/" + table);
    var tableFa = document.getElementById("FA/" + table);
    console.log(tableFa);

    if (tableContent.style.visibility == "hidden") {
        tableContent.style.visibility = "visible";
        tableContent.style.width = "auto";
        tableContent.style.height = "auto";
        tableFa.className = "fa fa-angle-down";
    } else {
        tableContent.style.visibility = "hidden";
        tableContent.style.width = "0px";
        tableContent.style.height = "0px";
        tableFa.className = "fa fa-angle-right";
    }

}
