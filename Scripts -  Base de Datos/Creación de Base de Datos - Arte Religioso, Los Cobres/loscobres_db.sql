-- CREAR BASE DE DATOS
CREATE DATABASE IF NOT EXISTS loscobres_db;
USE loscobres_db;

-- TABLA USUARIO
CREATE TABLE USUARIO (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    apellido VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_conexion DATETIME,
    rut VARCHAR(12),
    telefono VARCHAR(20),
    activo BOOLEAN DEFAULT 1
);

-- TABLA CLIENTE
CREATE TABLE CLIENTE (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- TABLA OPERADOR
CREATE TABLE OPERADOR (
    id_operador INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    cargo VARCHAR(50),
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario)
);

-- TABLA CATEGORIA
CREATE TABLE CATEGORIA (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria TEXT,
    descripcion_categoria TEXT,
    id_padre INT,
    FOREIGN KEY (id_padre) REFERENCES CATEGORIA(id_categoria)
);

-- TABLA PRODUCTO
CREATE TABLE PRODUCTO (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre_producto TEXT,
    descripcion TEXT,
    precio_unitario DECIMAL(10,2),
    stock_actual DECIMAL(10,2),
    url_imagen_principal TEXT,
    activo BOOLEAN DEFAULT 1,
    id_categoria INT,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
);

-- TABLA IMAGEN_PRODUCTO
CREATE TABLE IMAGEN_PRODUCTO (
    id_imagen INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT,
    url_imagen TEXT,
    orden INT,
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto) ON DELETE CASCADE
);

-- TABLA LISTA_FAVORITOS
CREATE TABLE LISTA_FAVORITOS (
    id_lista INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    nombre_lista TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
);

-- TABLA ITEM_FAVORITO
CREATE TABLE ITEM_FAVORITO (
    id_item_favorito INT AUTO_INCREMENT PRIMARY KEY,
    id_lista INT,
    id_producto INT,
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    comentarios TEXT,
    FOREIGN KEY (id_lista) REFERENCES LISTA_FAVORITOS(id_lista),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

-- TABLA CARRITO_COMPRA
CREATE TABLE CARRITO_COMPRA (
    id_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
);

-- TABLA ITEM_CARRITO
CREATE TABLE ITEM_CARRITO (
    id_item_carrito INT AUTO_INCREMENT PRIMARY KEY,
    id_carrito INT,
    id_producto INT,
    cantidad DECIMAL(10,2),
    precio_unitario_momento DECIMAL(10,2),
    FOREIGN KEY (id_carrito) REFERENCES CARRITO_COMPRA(id_carrito),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);

-- TABLA PEDIDO
CREATE TABLE PEDIDO (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT,
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado TEXT,
    codigo_verificacion TEXT,
    fecha_retiro_estimada DATETIME,
    fecha_retiro_real DATETIME,
    boolean_retiro BOOLEAN,
    lugar_retiro TEXT,
    metodo_pago TEXT,
    comentarios_cliente TEXT,
    id_operador INT,
    id_op_registro INT,
    id_op_entrega INT,
    id_carrito INT,
    FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente),
    FOREIGN KEY (id_operador) REFERENCES OPERADOR(id_operador),
    FOREIGN KEY (id_op_registro) REFERENCES OPERADOR(id_operador),
    FOREIGN KEY (id_op_entrega) REFERENCES OPERADOR(id_operador),
    FOREIGN KEY (id_carrito) REFERENCES CARRITO_COMPRA(id_carrito)
);

-- TABLA DETALLE_PEDIDO
CREATE TABLE DETALLE_PEDIDO (
    id_detalle_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT,
    id_producto INT,
    cantidad DECIMAL(10,2),
    precio_unitario DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    FOREIGN KEY (id_pedido) REFERENCES PEDIDO(id_pedido),
    FOREIGN KEY (id_producto) REFERENCES PRODUCTO(id_producto)
);
