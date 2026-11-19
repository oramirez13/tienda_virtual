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

$resultado = quitarProductoDelCarrito($_POST["id_producto"]);

if ($esAjax) {
    responderCarritoAjax($resultado, $_POST["id_producto"]);
    exit;
}

// El campo del formulario determina el destino de la redirección.
if (isset($_POST["redireccionar_carrito"])) {
    header("Location: ver_carrito.php");
} else {
    $posicion = 0;
    if (isset($_POST["posicion"])) {
        $posicion = intval($_POST["posicion"]);
    }
    header("Location: tienda.php?posicion=" . $posicion);
}