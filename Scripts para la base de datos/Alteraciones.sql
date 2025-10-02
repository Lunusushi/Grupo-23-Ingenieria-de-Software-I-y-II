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