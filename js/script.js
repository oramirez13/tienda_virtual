// Controla el carrusel y las peticiones AJAX del carrito.

$(function () {
    $("#formulario-finalizar-compra").on("submit", function (event) {
        event.preventDefault();

        const formulario = $(this);
        const boton = formulario.find("button[type='submit']");
        boton.prop("disabled", true);

        $.ajax({
            url: formulario.attr("action"),
            type: "POST",
            data: formulario.serialize() + "&ajax=1",
            dataType: "json",
            success: function (respuesta) {
                $("#resultado-compra").removeClass("d-none");
                $("#total-confirmacion-compra").text(respuesta.total);
                $("#contenido-carrito, #formulario-contacto-compra, #carrito-vacio").addClass("d-none");
                $("#contador-carrito").text("");
                $("#resultado-compra").trigger("focus");
            },
            error: function (jqXHR, textStatus, errorThrown) {
                boton.prop("disabled", false);
                const respuesta = jqXHR.responseJSON;
                const mensaje = respuesta && respuesta.mensaje
                    ? respuesta.mensaje
                    : "No se pudo completar la solicitud (" + (errorThrown || textStatus) + ").";
                mostrarMensajeAjax(mensaje, "alert-danger");
            }
        });
    });

    $(".formulario-carrito-ajax").submit(function (event) {
        event.preventDefault();

        const formulario = $(this);
        const idProducto = formulario.find('[name="id_producto"]').val();
        const datos = {
            ajax: "1",
            id_producto: idProducto
        };
        const posicion = formulario.find('[name="posicion"]').val();

        if (posicion !== undefined) {
            datos.posicion = posicion;
        }
        if (formulario.find('[name="redireccionar_carrito"]').length > 0) {
            datos.redireccionar_carrito = "1";
        }

        const boton = formulario.find("button[type='submit']");
        boton.prop("disabled", true);

        $.ajax({
            url: formulario.attr("action"),
            type: "POST",
            data: datos,
            dataType: "json",
            success: function (respuesta) {
                boton.prop("disabled", false);

                if (respuesta.resultado !== "error") {
                    actualizarInterfazCarrito(idProducto, respuesta);
                }
                mostrarResultadoCarrito(respuesta.resultado);
            },
            error: function (jqXHR, textStatus, errorThrown) {
                boton.prop("disabled", false);
                const detalle = errorThrown || textStatus;
                mostrarMensajeAjax("No se pudo completar la petición (" + detalle + ", HTTP " + jqXHR.status + ").", "alert-danger");
            }
        });
    });
});

function actualizarInterfazCarrito(idProducto, respuesta) {
    const cantidad = parseInt(respuesta.cantidad, 10);
    const totalUnidades = parseInt(respuesta.total_unidades, 10);

    $("#contador-carrito").text(totalUnidades > 0 ? " (" + totalUnidades + ")" : "");

    const diapositiva = $("#producto-" + idProducto);
    if (diapositiva.length > 0) {
        diapositiva.attr("data-cantidad", cantidad);
        $("#cantidad-producto-" + idProducto).text(cantidad);
        $("#texto-unidad-" + idProducto).text(cantidad === 1 ? "unidad" : "unidades");
        $("#unidades-producto-" + idProducto).toggleClass("d-none", cantidad === 0);
        $("#formulario-quitar-" + idProducto).toggleClass("d-none", cantidad === 0);

        const carrusel = $("#carrusel-productos");
        const maximoCamiseta = parseInt(carrusel.attr("data-maximo-camiseta"), 10);
        const maximoTotal = parseInt(carrusel.attr("data-maximo-total"), 10);

        $(".carrusel-diapositiva").each(function () {
            const tarjeta = $(this);
            const id = tarjeta.attr("data-id-producto");
            const cantidadActual = parseInt(tarjeta.attr("data-cantidad"), 10);
            const puedeAgregar = cantidadActual < maximoCamiseta && totalUnidades < maximoTotal;
            $("#boton-agregar-" + id).prop("disabled", !puedeAgregar);
        });
    }

    const fila = $("#fila-producto-" + idProducto);
    if (fila.length > 0) {
        if (cantidad === 0) {
            fila.remove();
        } else {
            $("#cantidad-carrito-" + idProducto).text(cantidad);
            $("#subtotal-carrito-" + idProducto).text(respuesta.subtotal);
        }

        $("#total-unidades-carrito").text(totalUnidades);
        $("#total-compra-carrito").text(respuesta.total_compra);

        if (totalUnidades === 0) {
            $("#contenido-carrito").addClass("d-none");
            $("#carrito-vacio").removeClass("d-none");
        }
    }
}

function mostrarResultadoCarrito(resultado) {
    const mensajes = {
        ok: "El carrito se actualizó correctamente.",
        producto_no_existe: "El producto solicitado no existe.",
        limite_camiseta: "Se alcanzó el límite de unidades para esta camiseta.",
        limite_carrito: "Se alcanzó el límite total de unidades del carrito.",
        no_en_carrito: "El producto ya no está en el carrito.",
        error: "No se pudo actualizar el carrito."
    };
    const tipo = resultado === "ok" ? "alert-success" : (resultado === "error" ? "alert-danger" : "alert-warning");
    mostrarMensajeAjax(mensajes[resultado] || mensajes.error, tipo);
}

function mostrarMensajeAjax(mensaje, tipo) {
    $("#mensaje-ajax")
        .removeClass("d-none alert-success alert-warning alert-danger")
        .addClass(tipo)
        .text(mensaje);
}

window.onload = function () {

    const diapositivas = document.querySelectorAll(".carrusel-diapositiva");
    const indicadores = document.querySelectorAll(".carrusel-indicador");
    let diapositivaActual = 0;

    diapositivas.forEach(function (diapositiva, posicion) {
        if (diapositiva.classList.contains("activa")) {
            diapositivaActual = posicion;
        }
    });

    // Solo configura el carrusel si la página actual lo tiene
    if (diapositivas.length > 0) {

        const botonAnterior = document.getElementById("boton-anterior");
        const botonSiguiente = document.getElementById("boton-siguiente");

        botonAnterior.onclick = function () {
            cambiarDiapositiva(-1);
        };

        botonSiguiente.onclick = function () {
            cambiarDiapositiva(1);
        };

        indicadores.forEach(function (indicador, posicion) {
            indicador.onclick = function () {
                irADiapositiva(posicion);
            };
        });

        function cambiarDiapositiva(direccion) {
            diapositivas[diapositivaActual].classList.remove("activa");
            diapositivaActual = (diapositivaActual + direccion + diapositivas.length) % diapositivas.length;
            diapositivas[diapositivaActual].classList.add("activa");
            actualizarIndicadores();
        }

        function irADiapositiva(posicion) {
            diapositivas[diapositivaActual].classList.remove("activa");
            diapositivaActual = posicion;
            diapositivas[diapositivaActual].classList.add("activa");
            actualizarIndicadores();
        }

        function actualizarIndicadores() {
            indicadores.forEach(function (indicador, posicion) {
                if (posicion === diapositivaActual) {
                    indicador.classList.add("activo");
                } else {
                    indicador.classList.remove("activo");
                }
                indicador.setAttribute("aria-current", posicion === diapositivaActual ? "true" : "false");
            });
        }
    }
};