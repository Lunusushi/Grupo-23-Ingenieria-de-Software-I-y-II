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

CREATE TABLE IF NOT EXISTS PLANTILLA (
  id_plantilla INT AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(120) NOT NULL UNIQUE,
  activa       BOOLEAN DEFAULT 1,
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Relación N:N con productos (sólo agrupar y ordenar)
CREATE TABLE IF NOT EXISTS PLANTILLA_PRODUCTO (
  id_plantilla INT NOT NULL,
  id_producto  INT NOT NULL,
  orden        INT DEFAULT 0,
  PRIMARY KEY (id_plantilla, id_producto),
  CONSTRAINT fk_pp_plantilla FOREIGN KEY (id_plantilla)
    REFERENCES PLANTILLA(id_plantilla) ON DELETE CASCADE,
  CONSTRAINT fk_pp_producto FOREIGN KEY (id_producto)
    REFERENCES PRODUCTO(id_producto) ON DELETE CASCADE
);

CREATE INDEX idx_pp_plantilla_orden ON PLANTILLA_PRODUCTO (id_plantilla, orden);

USE loscobres_db;

-- Colecciones de categorías (tabla propia)
CREATE TABLE IF NOT EXISTS PLANTILLA_CAT (
  id_plantilla_cat INT AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(120) NOT NULL UNIQUE,
  activa           BOOLEAN DEFAULT 1,
  fecha_creacion   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Miembros de la colección (categorías + orden)
CREATE TABLE IF NOT EXISTS PLANTILLA_CAT_ITEM (
  id_plantilla_cat INT NOT NULL,
  id_categoria     INT NOT NULL,
  orden            INT DEFAULT 0,
  PRIMARY KEY (id_plantilla_cat, id_categoria),
  CONSTRAINT fk_pcat_item_plantilla
    FOREIGN KEY (id_plantilla_cat) REFERENCES PLANTILLA_CAT(id_plantilla_cat)
    ON DELETE CASCADE,
  CONSTRAINT fk_pcat_item_categoria
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA(id_categoria)
    ON DELETE CASCADE
);

CREATE INDEX idx_pcat_item_plantilla_orden
  ON PLANTILLA_CAT_ITEM (id_plantilla_cat, orden);