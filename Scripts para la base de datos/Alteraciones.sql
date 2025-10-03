USE loscobres_db;

-- Carrito puede ser de cliente o invitado (vía token de sesión)
ALTER TABLE CARRITO_COMPRA
  MODIFY id_cliente INT NULL,
  ADD COLUMN session_token VARCHAR(64) NULL UNIQUE,
  ADD INDEX idx_carrito_idcliente (id_cliente),
  ADD INDEX idx_carrito_session (session_token);

-- Pedido puede ser de cliente o invitado
ALTER TABLE PEDIDO
  MODIFY id_cliente INT NULL,
  ADD COLUMN guest_nombre   VARCHAR(100) NULL,
  ADD COLUMN guest_email    VARCHAR(150) NULL,
  ADD COLUMN guest_telefono VARCHAR(30)  NULL;

-- (rendimiento) índice para items
ALTER TABLE ITEM_CARRITO
  ADD INDEX idx_item_carrito_carrito (id_carrito);

-- Perfil: avatar/portada en USUARIO 
ALTER TABLE USUARIO
  ADD COLUMN avatar_url TEXT NULL,
  ADD COLUMN cover_url  TEXT NULL;

-- Direcciones de cliente (múltiples + favoritas)
CREATE TABLE IF NOT EXISTS DIRECCION (
  id_direccion INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT NOT NULL,
  alias VARCHAR(50) NULL,             -- "Casa", "Trabajo"
  calle VARCHAR(120) NOT NULL,
  comuna VARCHAR(80) NULL,
  ciudad VARCHAR(80) NULL,
  region VARCHAR(80) NULL,
  referencia TEXT NULL,
  es_favorita BOOLEAN DEFAULT 0,
  tipo ENUM('casa','trabajo','otra') DEFAULT 'otra',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_cliente) REFERENCES CLIENTE(id_cliente)
);

-- Etiquetas de producto (rápidas)
ALTER TABLE PRODUCTO
  ADD COLUMN es_nuevo   BOOLEAN DEFAULT 0,
  ADD COLUMN es_oferta  BOOLEAN DEFAULT 0,
  ADD COLUMN es_popular BOOLEAN DEFAULT 0;

-- Categorías visibles/activas (toggle)
ALTER TABLE CATEGORIA
  ADD COLUMN activa BOOLEAN DEFAULT 1;

-- PRODUCTO_PLANTILLA: presets para altas rápidas de productos
CREATE TABLE IF NOT EXISTS PRODUCTO_PLANTILLA (
  id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
  nombre_plantilla VARCHAR(120) NOT NULL,
  id_categoria INT NULL,
  nombre_sugerido VARCHAR(120) NULL,
  descripcion_sugerida TEXT NULL,
  precio_por_defecto DECIMAL(10,2) NULL,
  url_imagen_por_defecto TEXT NULL,
  es_nuevo_def BOOLEAN DEFAULT 0,
  es_oferta_def BOOLEAN DEFAULT 0,
  es_popular_def BOOLEAN DEFAULT 0,
  activa BOOLEAN DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
);