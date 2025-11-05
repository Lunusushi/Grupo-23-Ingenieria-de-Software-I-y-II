
Use loscobres_db;
-- Tabla CATEGORIA
INSERT INTO CATEGORIA (id_categoria, nombre_categoria, descripcion_categoria, id_padre)
VALUES 
(1, 'Devocionales', 'Libros y materiales de oración', NULL),
(2, 'Accesorios', 'Artículos religiosos de uso diario', NULL);

-- Tabla PRODUCTO
INSERT INTO PRODUCTO (id_producto, nombre_producto, descripcion, precio_unitario, stock_actual, url_imagen_principal, activo, id_categoria)
VALUES 
(1, 'Biblia Católica Edición Pastoral', 'Biblia con tapa dura, tamaño mediano', 12000.00, 20, 'public/css/img/biblia.jpeg', TRUE, 1),
(2, 'Rosario de Madera', 'Rosario tradicional hecho a mano con cuentas de madera', 2500.00, 50, 'public/css/img/rosario_de_madera.jpeg', TRUE, 2);


-- Tabla IMAGEN_PRODUCTO
INSERT INTO IMAGEN_PRODUCTO (id_imagen, id_producto, url_imagen, orden)
VALUES 
(1, 1, 'public/css/img/rosario_de_madera.jpeg', 1),
(2, 2, 'public/css/img/biblia.jpeg', 1);
