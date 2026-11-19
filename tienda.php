<?php
include_once "encabezado.php";
include_once "funciones.php";
$productos = obtenerProductos();
$totalProductos = count($productos);

// Recupera por GET la posición conservada en la redirección del formulario.
$posicionActiva = 0;
if (isset($_GET["posicion"])) {
    $posicionActiva = intval($_GET["posicion"]);
}
if ($posicionActiva < 0 || $posicionActiva >= $totalProductos) {
    $posicionActiva = 0;
}

$cantidades = obtenerCantidadesDelCarrito();
$totalUnidades = obtenerTotalUnidadesEnCarrito();
?>
<h1 class="mb-4">Gran variedad de camisetas estampadas</h1>
<div id="mensaje-ajax" class="alert d-none" role="status" aria-live="polite"></div>

<?php
// Muestra el resultado del procesamiento recibido por GET tras la redirección.
if (isset($_GET["mensaje"])) {
    if ($_GET["mensaje"] == "producto_no_existe") {
        echo '<div class="alert alert-warning">El producto solicitado no existe.</div>';
    } elseif ($_GET["mensaje"] == "limite_camiseta") {
        echo '<div class="alert alert-warning">No puedes agregar más de 12 unidades de la misma camiseta.</div>';
    } elseif ($_GET["mensaje"] == "limite_carrito") {
        echo '<div class="alert alert-warning">El carrito ya tiene el máximo de 50 unidades.</div>';
    }
}
?>

<div class="carrusel" id="carrusel-productos"
    data-maximo-camiseta="<?php echo MAXIMO_POR_CAMISETA ?>"
    data-maximo-total="<?php echo MAXIMO_TOTAL_UNIDADES ?>">

    <div class="carrusel-contenedor">

        <?php
        $posicion = 0;
        foreach ($productos as $producto) {
            // Escapa datos del catálogo antes de insertarlos en el HTML (XSS).
            $nombre = htmlspecialchars($producto->nombre);
            $imagen = htmlspecialchars($producto->imagen);
            $idProducto = htmlspecialchars($producto->id);
            $cantidadEnCarrito = 0;
            if (isset($cantidades[$producto->id])) {
                $cantidadEnCarrito = intval($cantidades[$producto->id]);
            }
            $puedeAgregar = ($cantidadEnCarrito < MAXIMO_POR_CAMISETA) && ($totalUnidades < MAXIMO_TOTAL_UNIDADES);
        ?>

            <div id="producto-<?php echo $idProducto ?>"
                class="carrusel-diapositiva <?php echo ($posicion === $posicionActiva) ? "activa" : "" ?>"
                data-id-producto="<?php echo $idProducto ?>"
                data-cantidad="<?php echo $cantidadEnCarrito ?>">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="card-title"><?php echo $nombre ?></h3>
                        <img src="<?php echo $imagen ?>" alt="<?php echo $nombre ?>" class="carrusel-imagen img-fluid mb-3">
                        <h5 class="text-primary">₡<?php echo number_format($producto->precio, 2) ?></h5>

                        <p id="unidades-producto-<?php echo $idProducto ?>"
                            class="text-muted <?php echo ($cantidadEnCarrito > 0) ? "" : "d-none" ?>">
                            En tu carrito: <span id="cantidad-producto-<?php echo $idProducto ?>"><?php echo $cantidadEnCarrito ?></span>
                            <span id="texto-unidad-<?php echo $idProducto ?>"><?php echo ($cantidadEnCarrito === 1) ? "unidad" : "unidades" ?></span>
                        </p>

                        <!-- AJAX usa este POST; sin JavaScript se conserva la redirección tradicional. -->
                        <form action="agregar_al_carrito.php" method="post" class="d-inline-block formulario-carrito-ajax">
                            <input type="hidden" name="id_producto" value="<?php echo $idProducto ?>">
                            <!-- La redirección tradicional conserva la diapositiva seleccionada. -->
                            <input type="hidden" name="posicion" value="<?php echo $posicion ?>">
                            <?php if ($puedeAgregar) { ?>
                                <button id="boton-agregar-<?php echo $idProducto ?>" type="submit" class="btn btn-primary">Agregar al carrito</button>
                            <?php } elseif ($cantidadEnCarrito >= MAXIMO_POR_CAMISETA) { ?>
                                <button id="boton-agregar-<?php echo $idProducto ?>" type="submit" class="btn btn-secondary" disabled>Límite: 12 por camiseta</button>
                            <?php } else { ?>
                                <button id="boton-agregar-<?php echo $idProducto ?>" type="submit" class="btn btn-secondary" disabled>Carrito lleno (50)</button>
                            <?php } ?>
                        </form>

                        <form id="formulario-quitar-<?php echo $idProducto ?>" action="eliminar_del_carrito.php" method="post"
                            class="d-inline-block formulario-carrito-ajax <?php echo ($cantidadEnCarrito > 0) ? "" : "d-none" ?>">
                            <input type="hidden" name="id_producto" value="<?php echo $idProducto ?>">
                            <input type="hidden" name="posicion" value="<?php echo $posicion ?>">
                            <button id="boton-quitar-<?php echo $idProducto ?>" type="submit" class="btn btn-outline-danger">Quitar</button>
                        </form>
                    </div>
                </div>
            </div>

        <?php
            $posicion++;
        }
        ?>

    </div>

    <button id="boton-anterior" type="button" class="carrusel-flecha carrusel-anterior" aria-label="Producto anterior">&lt;</button>

    <button id="boton-siguiente" type="button" class="carrusel-flecha carrusel-siguiente" aria-label="Producto siguiente">&gt;</button>

    <div class="carrusel-indicadores">
        <?php
        for ($i = 0; $i < $totalProductos; $i++) {
        ?>
            <button type="button"
                class="carrusel-indicador <?php echo ($i === $posicionActiva) ? "activo" : "" ?>"
                aria-label="Mostrar producto <?php echo $i + 1 ?>"
                aria-current="<?php echo ($i === $posicionActiva) ? "true" : "false" ?>"></button>
        <?php
        }
        ?>
    </div>

</div>

<?php include_once "pie.php" ?>