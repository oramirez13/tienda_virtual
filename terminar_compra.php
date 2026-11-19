<?php
include_once "funciones.php";
iniciarSesionSiNoEstaIniciada();

$esAjax = isset($_POST["ajax"]) && $_POST["ajax"] === "1";
$esPost = isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "POST";
$tokenValido = $esPost
    && isset($_POST["token_compra"], $_SESSION["token_compra"])
    && is_string($_POST["token_compra"])
    && hash_equals($_SESSION["token_compra"], $_POST["token_compra"]);

$estado = "error";
$codigoHttp = 400;
$mensaje = "No se pudo finalizar la compra. Inténtalo de nuevo.";
$productos = [];
$total = 0;
$totalUnidades = 0;

if (!$esPost) {
    $codigoHttp = 405;
    $mensaje = "Envía el formulario para finalizar la compra.";
} elseif (!$tokenValido) {
    $codigoHttp = 403;
    $mensaje = "La solicitud expiró o no es válida. Vuelve al carrito e inténtalo de nuevo.";
} else {
    $nombre = isset($_POST["nombre"]) && is_string($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $correo = isset($_POST["correo"]) && is_string($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $telefono = isset($_POST["telefono"]) && is_string($_POST["telefono"]) ? trim($_POST["telefono"]) : "";

    if ($nombre === "" || $correo === "" || $telefono === "") {
        $codigoHttp = 422;
        $mensaje = "Completa el nombre, el correo electrónico y el teléfono.";
    } elseif (strlen($nombre) > 600 || strlen($correo) > 254 || strlen($telefono) > 160 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $codigoHttp = 422;
        $mensaje = "Revisa los datos ingresados. El correo debe tener un formato válido.";
    } else {
        $productos = obtenerProductosEnCarrito();
        if (count($productos) === 0) {
            $codigoHttp = 409;
            $mensaje = "Tu carrito está vacío. Agrega productos antes de finalizar.";
        } else {
            foreach ($productos as $producto) {
                $total += $producto->precio * $producto->cantidad;
                $totalUnidades += $producto->cantidad;
            }

            if (!guardarDatosCompraEnArchivo($nombre, $correo, $telefono)) {
                $codigoHttp = 500;
                $mensaje = "No se pudieron guardar tus datos. El carrito sigue disponible para reintentar.";
            } elseif (!vaciarCarrito()) {
                $codigoHttp = 500;
                $mensaje = "Los datos se guardaron, pero no se pudo limpiar el carrito. Contacta a la tienda antes de reintentar.";
            } else {
                unset($_SESSION["token_compra"]);
                $estado = "ok";
                $codigoHttp = 200;
                $mensaje = "Compra finalizada. Tus datos se guardaron correctamente.";
            }
        }
    }
}

if ($esAjax) {
    http_response_code($codigoHttp);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "resultado" => $estado,
        "mensaje" => $mensaje,
        "total" => number_format($total, 2, ".", ""),
        "total_unidades" => $totalUnidades
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($codigoHttp !== 200) {
    http_response_code($codigoHttp);
}
include_once "encabezado.php";

if ($estado !== "ok") {
    $tipoAlerta = $codigoHttp === 409 ? "info" : "danger";
?>
    <div class="alert alert-<?php echo $tipoAlerta ?>" role="alert">
        <h1 class="alert-heading">No se pudo finalizar la compra</h1>
        <p><?php echo htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></p>
        <a href="ver_carrito.php" class="btn btn-primary">Volver al carrito</a>
    </div>
<?php
} else {
?>
    <h1 class="mb-4">Compra finalizada</h1>
    <p class="lead">Gracias por tu compra. Tus datos de contacto quedaron guardados en el servidor.</p>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Detalle</th>
                    <th>Precio</th>
                    <th class="text-center">Cantidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto->nombre, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?php echo htmlspecialchars($producto->detalle, ENT_QUOTES, "UTF-8") ?></td>
                        <td>₡<?php echo number_format($producto->precio, 2) ?></td>
                        <td class="text-center"><?php echo (int)$producto->cantidad ?></td>
                        <td>₡<?php echo number_format($producto->precio * $producto->cantidad, 2) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total (<?php echo $totalUnidades ?> unidades)</strong></td>
                    <td colspan="2"><strong>₡<?php echo number_format($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <a href="tienda.php" class="btn btn-primary">Volver a la tienda</a>
<?php
}
?>
<?php include_once "pie.php" ?>