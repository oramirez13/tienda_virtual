# Tienda virtual

Tienda virtual de camisetas estampadas con un carrito de compras funcional.
El carrito se asocia a la sesión del servidor mediante `session_id()` y se
almacena en la base de datos `tienda1`. El catálogo y la vista del carrito
usan jQuery `$.ajax()` para agregar o quitar productos sin recargar la página;
la finalización de compra también usa AJAX. PHP devuelve los resultados en
JSON. Los formularios conservan el envío POST tradicional como respaldo si
JavaScript no está disponible.

---

## 1. Flujo de la aplicación

La aplicación presenta un recorrido lineal que parte del catálogo, permite
agregar y quitar camisetas, consultar el carrito y finalizar la compra.

### 1.1 Entrada

`index.php` redirige con `header("Location: tienda.php")`, de modo que la
aplicación inicia siempre en el catálogo.

### 1.2 Catálogo en carrusel

`tienda.php` obtiene los productos con `obtenerProductos()` y los presenta
en un carrusel con una diapositiva por camiseta. Cada diapositiva muestra
el nombre, la imagen y el precio del producto, junto con los formularios
**Agregar al carrito** y, si la camiseta ya tiene unidades en el carrito,
**Quitar**. Ambos formularios envían `id_producto` y `posicion` por POST:
el primero a `agregar_al_carrito.php` y el segundo a
`eliminar_del_carrito.php`.

`js/script.js` intercepta el envío de estos formularios y usa `$.ajax()` con
método `POST` y `dataType: "json"`. En `success`, actualiza la cantidad del
producto y el contador del encabezado sin recargar; en `error`, muestra el
estado de la petición. Si el manejador de JavaScript no se ejecuta, el
formulario sigue el flujo POST y redirección descrito abajo.

El carrusel conserva la diapositiva en la que se encontraba el usuario:
los formularios registran la posición de la diapositiva, los archivos de
proceso redirigen a `tienda.php?posicion=N`, la página asigna la clase
`activa` a esa diapositiva y `js/script.js` la utiliza como posición
inicial al cargar. De esta forma, la camiseta seleccionada permanece visible
después de recargar la página.

### 1.3 Agregar al carrito

`agregar_al_carrito.php` invoca a `agregarProductoAlCarrito()`, que realiza
las siguientes comprobaciones en orden:

1. Verifica que el id exista en la tabla `productos`; si no existe,
   devuelve `producto_no_existe`.
2. Verifica el límite por camiseta (12 unidades); si se alcanzó,
   devuelve `limite_camiseta`.
3. Verifica el límite total del carrito (50 unidades); si se alcanzó,
   devuelve `limite_carrito`.
4. Si las comprobaciones son correctas, suma 1 unidad con un UPDATE cuando
   el producto ya estaba en el carrito, o lo inserta con cantidad 1 cuando
   es nuevo (INSERT), y devuelve `ok`.

Cuando el resultado es `producto_no_existe`, `limite_camiseta` o
`limite_carrito`, la redirección agrega el parámetro `mensaje` a
`tienda.php`, que muestra la alerta correspondiente. Si se agrega el producto
correctamente (`ok`) o se produce otro error, la redirección no incluye ese
parámetro. Al alcanzar un límite, la tarjeta deshabilita el botón para agregar.

Cuando la solicitud incluye `ajax=1`, el procesador no redirige: llama a
`responderCarritoAjax()` y responde JSON con el resultado, la cantidad del
producto y los totales actuales. La misma función se usa al quitar productos.

### 1.4 Ver el carrito

`ver_carrito.php` obtiene los productos del carrito con
`obtenerProductosEnCarrito()` y los muestra en una tabla con nombre,
detalle, imagen, precio unitario, cantidad, subtotal (precio por cantidad)
y un botón **Quitar**. Al pie de la tabla se muestran el total de unidades
y el monto total de la compra. La barra de navegación de `encabezado.php`
muestra entre paréntesis el total de unidades mediante
`obtenerTotalUnidadesEnCarrito()`.

Al quitar un producto, jQuery envía el formulario por AJAX. La respuesta JSON
permite actualizar la fila, el subtotal y el total; si el carrito queda vacío,
se muestra el mensaje correspondiente sin recargar la página.

### 1.5 Quitar del carrito

El botón **Quitar** envía `id_producto` por POST a
`eliminar_del_carrito.php`, que invoca a `quitarProductoDelCarrito()`. Esta
función resta 1 unidad; si era la última, elimina la fila de la tabla. En
modo AJAX, el procesador devuelve JSON y la página actualiza el carrito sin
recargar. Si el identificador ya no está en el carrito, la respuesta lo indica
como `no_en_carrito`; una excepción de base de datos se devuelve como `false`.
Si JavaScript no intercepta el formulario, la redirección depende del origen:
a `ver_carrito.php` si incluye `redireccionar_carrito`, o a
`tienda.php?posicion=N` si proviene de la tienda.

### 1.6 Finalizar la compra

Al pulsar **Finalizar compra** en `ver_carrito.php`, se muestra un formulario
con nombre, correo y teléfono obligatorios. `js/script.js` intercepta su envío
y utiliza `$.ajax()` con POST para enviar los datos y el token de sesión a
`terminar_compra.php`.

El servidor valida el token, que los tres valores sean texto no vacío, sus
longitudes máximas y que el correo tenga formato válido.
`guardarDatosCompraEnArchivo()` abre `data/compras.txt` en modo binario de
adición (`ab`), escribe una fila CSV con fecha, nombre, correo y teléfono
usando `fputcsv()`, y cierra el archivo. El bloqueo de archivo
evita mezclar escrituras concurrentes. El carrito solo se vacía después de
guardar el registro. En AJAX, PHP responde JSON con estado y mensaje; la página
muestra el resultado sin recargar. Si falla la validación o el guardado, el
carrito queda disponible para reintentar. El formulario conserva el envío POST
HTML como respaldo si JavaScript no se ejecuta. No se procesan pagos ni se crean
órdenes.

### 1.7 Persistencia

El carrito se guarda en la tabla `carrito_usuarios` asociado al
`session_id()` de la sesión PHP. Cada sesión mantiene su propio carrito sin
necesidad de registro; los datos persisten al recargar la página o navegar
entre las páginas mientras esa sesión siga activa.

---

## 2. Base de datos

El script `tienda.sql`, incluido en el proyecto, crea la base de datos
`tienda1` y las tablas que todavía no existan. Se puede ejecutar nuevamente:
no elimina las tablas ni los carritos existentes; `INSERT IGNORE` agrega los
productos semilla que falten y conserva los registros con IDs ya presentes.

### Tabla `productos`

Almacena el catálogo de camisetas.

| Campo   | Tipo         | Descripción                          |
| ------- | ------------ | ------------------------------------ |
| id      | INT          | Identificador único (llave primaria) |
| nombre  | VARCHAR(100) | Nombre comercial de la camiseta      |
| detalle | TEXT         | Descripción del producto             |
| imagen  | VARCHAR(255) | Ruta de la imagen (carpeta `img/`)   |
| precio  | DOUBLE       | Precio unitario (8,500.00)           |

### Tabla `carrito_usuarios`

Almacena los productos de cada sesión con su cantidad.

| Campo       | Tipo        | Descripción                              |
| ----------- | ----------- | ---------------------------------------- |
| id_sesion   | VARCHAR(64) | Id de la sesión del servidor             |
| id_producto | INT         | Id del producto agregado                 |
| cantidad    | INT         | Unidades de ese producto (por defecto 1) |

La llave primaria es compuesta `(id_sesion, id_producto)`: cada sesión solo
puede tener una fila por producto, y la cantidad se suma o resta en esa
misma fila.

---

## 3. Estructura de archivos

```
tienda_virtual/
├── index.php                 Redirige a tienda.php
├── tienda.php                Catálogo con carrusel (página principal)
├── ver_carrito.php           Muestra el carrito con cantidades y subtotales
├── terminar_compra.php       Finaliza la compra y muestra el resumen
├── agregar_al_carrito.php    Recibe el formulario de agregar (suma 1 unidad)
├── eliminar_del_carrito.php  Recibe el formulario de quitar (resta 1 unidad)
├── funciones.php             Conexión MySQL y lógica del carrito
├── encabezado.php            Header: navbar con contador de unidades
├── pie.php                   Scripts de Bootstrap, jQuery y script.js
├── env.php                   Credenciales de la base de datos (formato INI)
├── data/
│   ├── .htaccess             Evita servir el archivo de compras por HTTP
│   └── compras.txt           Registro generado al finalizar compras
├── .gitignore                Excluye el registro con datos personales
├── tienda.sql                Script SQL que crea la base de datos
├── css/
│   └── style.css             Estilos: navbar, carrusel e imágenes
├── js/
│   └── script.js             AJAX del carrito y carrusel
└── img/                      Imágenes de las 10 camisetas e icono del carrito
```

---

## 4. Funciones

`funciones.php` concentra la lógica de acceso a datos y la gestión del
carrito:

| Función                                                    | Descripción                                       |
| ---------------------------------------------------------- | ------------------------------------------------- |
| `obtenerProductos()`                                       | Devuelve las camisetas del catálogo               |
| `obtenerProductosEnCarrito()`                              | Productos del carrito con su `cantidad`           |
| `obtenerCantidadesDelCarrito()`                            | Arreglo id => cantidad de la sesión               |
| `obtenerTotalUnidadesEnCarrito()`                          | Suma de todas las unidades del carrito            |
| `agregarProductoAlCarrito($idProducto)`                    | Suma 1 unidad respetando los límites              |
| `quitarProductoDelCarrito($idProducto)`                    | Resta 1 unidad; informa si el producto no estaba  |
| `vaciarCarrito()`                                          | Borra todo el carrito de la sesión                |
| `guardarDatosCompraEnArchivo($nombre, $correo, $telefono)` | Agrega los datos del cliente a `data/compras.txt` |
| `responderCarritoAjax($resultado, $idProducto)`            | Responde con el estado actual del carrito en JSON |
| `obtenerConexion()`                                        | Conecta a MySQL con PDO leyendo `env.php`         |
| `obtenerVariableDelEntorno($key)`                          | Lee las credenciales del archivo `env.php`        |
| `iniciarSesionSiNoEstaIniciada()`                          | Inicia la sesión si PHP aún no tiene una activa   |

Los límites de cantidad están definidos como constantes al inicio del
archivo:

```php
define("MAXIMO_POR_CAMISETA", 12);   // máximo de unidades de la misma camiseta
define("MAXIMO_TOTAL_UNIDADES", 50); // máximo de unidades totales del carrito
```

---

## 5. Seguridad en la salida de datos

Toda salida dinámica de texto se escapa con `htmlspecialchars()` antes de
imprimirse en pantalla (nombre, detalle, imagen, identificadores y el token de
la sesión); los importes se formatean con `number_format()`. Escapar la salida
evita que un texto malicioso contenido en la base de datos se interprete
como HTML, lo que constituye una protección básica contra XSS. Las consultas
se ejecutan con PDO y consultas preparadas, de modo que los datos enviados
por el formulario no se interpolan directamente en el SQL.

Las credenciales de la base de datos se guardan en `env.php`, cuyo contenido
empieza con `; <?php exit; ?>`: si el archivo se abre en el navegador, PHP
termina la ejecución de inmediato y no expone las credenciales.

Los datos de contacto de las compras se guardan en `data/compras.txt`, fuera de
la base de datos. Cada fila contiene fecha, nombre, correo y teléfono en formato
CSV. La carpeta `data/` incluye una regla de Apache para denegar su acceso HTTP;
el usuario con el que corre PHP debe tener permiso de escritura en esa carpeta.

La búsqueda de `env.php` se hace desde el directorio de `funciones.php`, no
desde el directorio de trabajo del servidor. El formulario de finalización
usa POST y un token guardado en la sesión para impedir que una navegación
externa vacíe el carrito.

El archivo `data/compras.txt` contiene datos personales y se excluye del
control de versiones mediante `.gitignore`.

---

## 6. Interfaz

### `encabezado.php` y `pie.php`

`encabezado.php` define la estructura HTML inicial y la barra de navegación
con el enlace **Carrito**, que muestra el total de unidades del usuario.
`pie.php` cierra la sección de contenido y carga los scripts (Bootstrap para
el menú desplegable, jQuery para AJAX y `js/script.js` para el carrusel y los
callbacks de las peticiones).

### `css/style.css`

Hoja de estilos propia, enlazada desde `encabezado.php`. Define la barra de
navegación, el carrusel (diapositivas ocultas con solo la `activa` visible,
flechas circulares e indicadores con botones accesibles por teclado), el
tamaño uniforme de las imágenes y el icono del carrito.

### `js/script.js`

`js/script.js` se carga al final de la página desde `pie.php`. El callback
`$(function () { ... })` conecta los formularios con `$.ajax()`: envía los datos
por POST, espera JSON y actualiza la vista en `success`; `error` informa si la
petición falla. El mismo archivo inicializa el carrusel dentro de
`window.onload`, toma la diapositiva marcada como `activa` y permite navegar
con flechas e indicadores.
