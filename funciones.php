<?php

// El carrito se persiste en la base de datos y se asocia a la sesión del servidor.

define("MAXIMO_POR_CAMISETA", 12);

define("MAXIMO_TOTAL_UNIDADES", 50);

function obtenerProductosEnCarrito()
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $sentencia = $bd->prepare("SELECT productos.id, productos.nombre, productos.detalle, productos.imagen, productos.precio, carrito_usuarios.cantidad
        FROM productos
        INNER JOIN carrito_usuarios
        ON productos.id = carrito_usuarios.id_producto
        WHERE carrito_usuarios.id_sesion = ?
        ORDER BY productos.id");
        $idSesion = session_id();
        $sentencia->execute([$idSesion]);
        return $sentencia->fetchAll();
    } catch (Exception $er) {
        error_log("Error en obtenerProductosEnCarrito: " . $er->getMessage());
        return [];
    }
}

function obtenerCantidadesDelCarrito()
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $sentencia = $bd->prepare("SELECT id_producto, cantidad FROM carrito_usuarios WHERE id_sesion = ?");
        $idSesion = session_id();
        $sentencia->execute([$idSesion]);
        return $sentencia->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $er) {
        error_log("Error en obtenerCantidadesDelCarrito: " . $er->getMessage());
        return [];
    }
}

function obtenerTotalUnidadesEnCarrito()
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $sentencia = $bd->prepare("SELECT COALESCE(SUM(cantidad), 0) AS total FROM carrito_usuarios WHERE id_sesion = ?");
        $idSesion = session_id();
        $sentencia->execute([$idSesion]);
        $fila = $sentencia->fetch();
        return (int)$fila->total;
    } catch (Exception $er) {
        error_log("Error en obtenerTotalUnidadesEnCarrito: " . $er->getMessage());
        return 0;
    }
}

function quitarProductoDelCarrito($idProducto)
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $idSesion = session_id();
        $consultaCantidad = $bd->prepare("SELECT cantidad FROM carrito_usuarios WHERE id_sesion = ? AND id_producto = ?");
        $consultaCantidad->execute([$idSesion, $idProducto]);
        $cantidad = $consultaCantidad->fetchColumn();
        if ($cantidad === false) {
            return "no_en_carrito";
        }
        $cantidad = (int)$cantidad;

        if ($cantidad <= 1) {
            $sentencia = $bd->prepare("DELETE FROM carrito_usuarios WHERE id_sesion = ? AND id_producto = ?");
            $sentencia->execute([$idSesion, $idProducto]);
        } else {
            $sentencia = $bd->prepare("UPDATE carrito_usuarios SET cantidad = cantidad - 1 WHERE id_sesion = ? AND id_producto = ?");
            $sentencia->execute([$idSesion, $idProducto]);
        }
        return $sentencia->rowCount() > 0 ? "ok" : "no_en_carrito";
    } catch (Exception $er) {
        error_log("Error en quitarProductoDelCarrito: " . $er->getMessage());
        return false;
    }
}

function vaciarCarrito()
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $idSesion = session_id();
        $sentencia = $bd->prepare("DELETE FROM carrito_usuarios WHERE id_sesion = ?");
        return $sentencia->execute([$idSesion]);
    } catch (Exception $er) {
        error_log("Error en vaciarCarrito: " . $er->getMessage());
        return false;
    }
}

function guardarDatosCompraEnArchivo($nombre, $correo, $telefono)
{
    $directorio = __DIR__ . DIRECTORY_SEPARATOR . "data";
    if (!is_dir($directorio) && !@mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        error_log("No se pudo crear el directorio para los registros de compra.");
        return false;
    }

    $archivo = $directorio . DIRECTORY_SEPARATOR . "compras.txt";
    $manejador = @fopen($archivo, "ab");
    if ($manejador === false) {
        error_log("No se pudo abrir el archivo de registros de compra.");
        return false;
    }

    $guardado = false;
    if (flock($manejador, LOCK_EX)) {
        $guardado = fputcsv($manejador, [date(DATE_ATOM), $nombre, $correo, $telefono]) !== false;
        fflush($manejador);
        flock($manejador, LOCK_UN);
    }
    fclose($manejador);

    if (!$guardado) {
        error_log("No se pudieron guardar los datos de la compra en el archivo.");
    }
    return $guardado;
}

function obtenerProductos()
{
    try {
        $bd = obtenerConexion();
        $sentencia = $bd->query("SELECT id, nombre, detalle, imagen, precio FROM productos ORDER BY id");
        return $sentencia->fetchAll();
    } catch (Exception $er) {
        error_log("Error en obtenerProductos: " . $er->getMessage());
        return [];
    }
}

function agregarProductoAlCarrito($idProducto)
{
    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $idSesion = session_id();
        // El id llega desde el formulario; la consulta preparada evita inyección SQL.
        $sentenciaProducto = $bd->prepare("SELECT id FROM productos WHERE id = ?");
        $sentenciaProducto->execute([$idProducto]);
        $productoEncontrado = $sentenciaProducto->fetch();
        if (!$productoEncontrado) {
            return "producto_no_existe";
        }
        $cantidades = obtenerCantidadesDelCarrito();
        $cantidadActual = $cantidades[$idProducto] ?? 0;
        if ($cantidadActual >= MAXIMO_POR_CAMISETA) {
            return "limite_camiseta";
        }
        $totalUnidades = obtenerTotalUnidadesEnCarrito();
        if ($totalUnidades >= MAXIMO_TOTAL_UNIDADES) {
            return "limite_carrito";
        }
        if ($cantidadActual > 0) {
            $sentencia = $bd->prepare("UPDATE carrito_usuarios SET cantidad = cantidad + 1 WHERE id_sesion = ? AND id_producto = ?");
            $sentencia->execute([$idSesion, $idProducto]);
        } else {
            $sentencia = $bd->prepare("INSERT INTO carrito_usuarios(id_sesion, id_producto, cantidad) VALUES (?, ?, 1)");
            $sentencia->execute([$idSesion, $idProducto]);
        }
        return "ok";
    } catch (Exception $er) {
        error_log("Error en agregarProductoAlCarrito: " . $er->getMessage());
        return "error";
    }
}

// Prepara el estado actualizado del carrito para una respuesta AJAX en JSON.
function responderCarritoAjax($resultado, $idProducto)
{
    $cantidad = 0;
    $subtotal = 0;
    $total = 0;
    $totalUnidades = 0;

    try {
        $bd = obtenerConexion();
        iniciarSesionSiNoEstaIniciada();
        $sentencia = $bd->prepare("SELECT productos.id, productos.precio, carrito_usuarios.cantidad
            FROM productos
            INNER JOIN carrito_usuarios ON productos.id = carrito_usuarios.id_producto
            WHERE carrito_usuarios.id_sesion = ?");
        $sentencia->execute([session_id()]);

        foreach ($sentencia->fetchAll() as $producto) {
            $subtotalProducto = $producto->precio * $producto->cantidad;
            $total += $subtotalProducto;
            $totalUnidades += $producto->cantidad;

            if ((int)$producto->id === (int)$idProducto) {
                $cantidad = (int)$producto->cantidad;
                $subtotal = $subtotalProducto;
            }
        }
    } catch (Exception $er) {
        error_log("Error en responderCarritoAjax: " . $er->getMessage());
        $resultado = "error";
    }

    header("Content-Type: application/json; charset=utf-8");
    if ($resultado === "error") {
        http_response_code(500);
    }
    echo json_encode([
        "resultado" => $resultado,
        "cantidad" => $cantidad,
        "subtotal" => number_format($subtotal, 2, ".", ","),
        "total_unidades" => $totalUnidades,
        "total_compra" => number_format($total, 2, ".", ",")
    ], JSON_UNESCAPED_UNICODE);
}

function iniciarSesionSiNoEstaIniciada()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function obtenerVariableDelEntorno($key)
{
    if (defined("_ENV_CACHE")) {
        $vars = _ENV_CACHE;
    } else {
        $file = __DIR__ . DIRECTORY_SEPARATOR . "env.php";
        if (!file_exists($file)) {
            throw new Exception("El archivo de las variables de entorno ($file) no existe. Favor de crearlo");
        }
        $vars = parse_ini_file($file);
        define("_ENV_CACHE", $vars);
    }
    if (isset($vars[$key])) {
        return $vars[$key];
    } else {
        throw new Exception("La clave especificada (" . $key . ") no existe en el archivo de las variables de entorno");
    }
}

function obtenerConexion()
{
    $password = obtenerVariableDelEntorno("MYSQL_PASSWORD");
    $user = obtenerVariableDelEntorno("MYSQL_USER");
    $dbName = obtenerVariableDelEntorno("MYSQL_DATABASE_NAME");
    $database = new PDO('mysql:host=localhost;dbname=' . $dbName, $user, $password);
    $database->query("set names utf8;");
    $database->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
    return $database;
}