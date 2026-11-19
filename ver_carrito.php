<?php
include_once "encabezado.php";
include_once "funciones.php";
iniciarSesionSiNoEstaIniciada();
$productos = obtenerProductosEnCarrito();
$hayProductos = count($productos) > 0;
if ($hayProductos && !isset($_SESSION["token_compra"])) {
    $_SESSION["token_compra"] = bin2hex(random_bytes(32));
}
?>
<div id="mensaje-ajax" class="alert d-none" role="status" aria-live="polite"></div>

<div id="carrito-vacio" class="alert alert-info text-center <?php echo $hayProductos ? "d-none" : "" ?>">
    <h1 class="alert-heading">Todavía no hay productos</h1>
    <p>Visita la tienda para agregar productos a tu carrito.</p>
    <a href="tienda.php" class="btn btn-primary">Ver tienda</a>
</div>

<div id="contenido-carrito" class="<?php echo $hayProductos ? "" : "d-none" ?>">
    <h1 class="mb-4">Mi carrito de compras</h1>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Detalle</th>
                    <th>Imagen</th>
                    <th>Precio</th>
                    <th class="text-center">Cantidad</th>
                    <th>Subtotal</th>
                    <th>Quitar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = 0;
                $totalUnidades = 0;
                foreach ($productos as $producto) {
                    $nombre = htmlspecialchars($producto->nombre);
                    $detalle = htmlspecialchars($producto->detalle);
                    $imagen = htmlspecialchars($producto->imagen);
                    $idProducto = htmlspecialchars($producto->id);
                    $subtotal = $producto->precio * $producto->cantidad;
                    $total += $subtotal;
                    $totalUnidades += $producto->cantidad;
                ?>
                    <tr id="fila-producto-<?php echo $idProducto ?>">
                        <td><?php echo $nombre ?></td>
                        <td><?php echo $detalle ?></td>
                        <td>
                            <img src="<?php echo $imagen ?>" alt="<?php echo $nombre ?>" class="imagen-carrito">
                        </td>
                        <td>₡<?php echo number_format($producto->precio, 2) ?></td>
                        <td class="text-center"><span id="cantidad-carrito-<?php echo $idProducto ?>"><?php echo htmlspecialchars($producto->cantidad) ?></span></td>
                        <td>₡<span id="subtotal-carrito-<?php echo $idProducto ?>"><?php echo number_format($subtotal, 2) ?></span></td>
                        <td>
                            <form action="eliminar_del_carrito.php" method="post" class="formulario-carrito-ajax">
                                <input type="hidden" name="id_producto" value="<?php echo $idProducto ?>">
                                <input type="hidden" name="redireccionar_carrito">
                                <button type="submit" class="btn btn-danger btn-sm">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total (<span id="total-unidades-carrito"><?php echo $totalUnidades ?></span> unidades)</strong></td>
                    <td colspan="2"><strong>₡<span id="total-compra-carrito"><?php echo number_format($total, 2) ?></span></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <details id="formulario-contacto-compra" class="formulario-compra mt-4">
        <summary class="btn btn-success btn-lg">Finalizar compra</summary>
        <h2 id="titulo-contacto-compra" class="h4">Datos de contacto</h2>
        <p>Completa tus datos para registrar la compra.</p>
        <form action="terminar_compra.php" method="post" id="formulario-finalizar-compra">
            <input type="hidden" name="token_compra" value="<?php echo htmlspecialchars($_SESSION["token_compra"], ENT_QUOTES, "UTF-8") ?>">
            <div class="mb-3">
                <label for="nombre-comprador" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre-comprador" name="nombre" autocomplete="name" maxlength="150" required>
            </div>
            <div class="mb-3">
                <label for="correo-comprador" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo-comprador" name="correo" autocomplete="email" maxlength="254" required>
            </div>
            <div class="mb-3">
                <label for="telefono-comprador" class="form-label">Teléfono de contacto</label>
                <input type="tel" class="form-control" id="telefono-comprador" name="telefono" autocomplete="tel" maxlength="40" required>
            </div>
            <button type="submit" class="btn btn-primary">Confirmar compra</button>
        </form>
    </details>
</div>
<div id="resultado-compra" class="container alert alert-success mt-4 d-none" role="status" aria-live="polite" tabindex="-1">
    <h2 class="h4">Compra finalizada</h2>
    <p class="mb-1">Tus datos se guardaron correctamente.</p>
    <p class="mb-0">Total: ₡<span id="total-confirmacion-compra"></span></p>
    <a href="tienda.php" class="btn btn-success mt-3">Volver a la tienda</a>
</div>
<?php include_once "pie.php" ?>