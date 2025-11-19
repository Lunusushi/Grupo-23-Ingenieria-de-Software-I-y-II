select * from operador;
select * from usuario;
select * from cliente;
select * from producto;
select * from pedido;

delete from operador where id_usuario = 1;
delete from cliente where id_usuario = 3;

insert into operador (id_operador, id_usuario, cargo)
values(2, 3, "administrador");

delete from producto where id_producto = 2;