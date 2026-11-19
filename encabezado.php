<?php
include_once "funciones.php";
iniciarSesionSiNoEstaIniciada();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda virtual</title>

    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@latest/dist/css/bootstrap.min.css"
      rel="stylesheet"
      crossorigin="anonymous"
    />

    <link rel="stylesheet" href="css/style.css" />
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-tienda">
        <div class="container">
            <a class="navbar-brand" href="tienda.php">Tienda virtual</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavegacion" aria-controls="menuNavegacion" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuNavegacion">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="ver_carrito.php">
                            <img src="img/icons8-shopping-cart-48.png" alt="Carrito de compras" class="icono-carrito me-1">
                            Carrito
                            <span id="contador-carrito"><?php
                                $conteo = obtenerTotalUnidadesEnCarrito();
                                if ($conteo > 0) {
                                    printf(" (%d)", $conteo);
                                }
                            ?></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-4">