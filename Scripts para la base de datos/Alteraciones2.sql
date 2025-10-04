-- calmao yon, no usen esta wea aun

SELECT dp.id_producto, SUM(dp.cantidad) AS ventas_30d
FROM DETALLE_PEDIDO dp
JOIN PEDIDO pe ON pe.id_pedido = dp.id_pedido
WHERE pe.fecha_pedido >= (CURRENT_DATE - INTERVAL 30 DAY)
GROUP BY dp.id_producto;

-- Primero, limpiar flag
UPDATE PRODUCTO SET es_popular = 0;

-- Marcar los top 5 por categoría
-- (una forma es: para cada categoría, calcular ranking)
WITH ventas AS (
  SELECT p.id_producto, p.id_categoria, COALESCE(SUM(dp.cantidad),0) AS ventas_30d
  FROM PRODUCTO p
  LEFT JOIN DETALLE_PEDIDO dp ON dp.id_producto = p.id_producto
  LEFT JOIN PEDIDO pe ON pe.id_pedido = dp.id_pedido
       AND pe.fecha_pedido >= (CURRENT_DATE - INTERVAL 30 DAY)
  WHERE p.activo = 1
  GROUP BY p.id_producto, p.id_categoria
),
ranked AS (
  SELECT v.*,
         DENSE_RANK() OVER (PARTITION BY v.id_categoria ORDER BY v.ventas_30d DESC) AS rnk
  FROM ventas v
)
UPDATE PRODUCTO p
JOIN ranked r ON r.id_producto = p.id_producto
SET p.es_popular = 1
WHERE r.rnk <= 5 AND r.ventas_30d > 0;
