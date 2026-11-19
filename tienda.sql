CREATE DATABASE IF NOT EXISTS tienda1;

USE tienda1;

CREATE TABLE IF NOT EXISTS productos (
    id INT PRIMARY KEY,
    nombre VARCHAR(100),
    detalle TEXT,
    imagen VARCHAR(255),
    precio DOUBLE
);

INSERT IGNORE INTO productos (id, nombre, detalle, imagen, precio) VALUES
(1,  'Camiseta Mostaza',          'Camiseta 100% algodón estampada, tono mostaza, corte clásico. Tallas S a XXL.',            'img/tshirt_01.jpg', 8500),
(2,  'Camiseta Azul Estampada',   'Camiseta 100% algodón estampada en azul, con diseño exclusivo al frente y tela fresca.',   'img/tshirt_02.jpg', 8500),
(3,  'Camiseta Negra Clásica',    'Camiseta 100% algodón estampada en negro, diseño discreto de estilo clásico.',             'img/tshirt_03.jpg', 8500),
(4,  'Camiseta Negra',                      'Camiseta 100% algodón estampada en negro, tela ligera ideal para clima cálido.', 'img/tshirt_04.jpg', 8500),
(5,  'Camiseta Gris',                               'Camiseta 100% algodón estampada en gris, acabado mate y corte moderno.', 'img/tshirt_05.jpg', 8500),
(6,  'Camiseta Rosa',             'Camiseta 100% algodón estampada en rosa, tacto suave y color firme al lavado.',            'img/tshirt_06.jpg', 8500),
(7,  'Camiseta Verde Menta',             'Camiseta 100% algodón estampada en verde menta, tono fresco resistente al lavado.', 'img/tshirt_07.jpg', 8500),
(8,  'Camiseta Café',                  'Camiseta 100% algodón estampada en tono café, diseño artístico de edición limitada.', 'img/tshirt_08.jpg', 8500),
(9,  'Camiseta Roja Estampada',   'Camiseta 100% algodón premium estampada en rojo, diseño al frente con doble costura.',     'img/tshirt_09.jpg', 8500),
(10, 'Camiseta Azul Marino',            'Camiseta 100% algodón estampada en azul marino, color oscuro que combina con todo.', 'img/tshirt_10.jpg', 8500);

SELECT * FROM productos;

-- Relaciona el carrito persistido con la sesión del servidor.
CREATE TABLE IF NOT EXISTS carrito_usuarios (
    id_sesion VARCHAR(64),
    id_producto INT,
    cantidad INT NOT NULL DEFAULT 1,
    PRIMARY KEY (id_sesion, id_producto)
);