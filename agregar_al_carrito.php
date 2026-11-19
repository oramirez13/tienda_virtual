<?php
// Procesa el POST normal con redirección o devuelve JSON en modo AJAX.
include_once "funciones.php";

$esAjax = isset($_POST["ajax"]) && $_POST["ajax"] === "1";

if (!isset($_POST["id_producto"])) {
    if ($esAjax) {
        http_response_code(400);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["resultado" => "error"]);
        exit;
    }
    exit("No hay id_producto");
}

// El resultado indica si se agregó o por qué no pudo agregarse.
$resultado = agregarProductoAlCarrito($_POST["id_producto"]);

if ($esAjax) {
    responderCarritoAjax($resultado, $_POST["id_producto"]);
    exit;
}

// Conserva la diapositiva seleccionada a través del POST y la redirección GET.
$posicion = 0;
if (isset($_POST["posicion"])) {
    $posicion = intval($_POST["posicion"]);
}

$destino = "tienda.php?posicion=" . $posicion;

if ($resultado == "producto_no_existe") {
    $destino .= "&mensaje=producto_no_existe";
} elseif ($resultado == "limite_camiseta") {
    $destino .= "&mensaje=limite_camiseta";
} elseif ($resultado == "limite_carrito") {
    $destino .= "&mensaje=limite_carrito";
}

header("Location: " . $destino);