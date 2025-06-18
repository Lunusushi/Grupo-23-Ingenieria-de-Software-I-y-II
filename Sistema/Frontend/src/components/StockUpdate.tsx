import React, { useState } from 'react'

type Product = {
  id: string
  nombre: string
  stock: number
}

export default function StockUpdate() {
  const [producto, setProducto] = useState<Product | null>(null)
  const [cantidadComprada, setCantidadComprada] = useState(1)

  const handleBuscar = () => {
    // Mock de búsqueda; en producción, llama al backend para obtener el producto
    const mockProducto = { id: '123ABC', nombre: 'Rosario de madera', stock: 5 }
    setProducto(mockProducto)
  }

  const handleActualizar = () => {
    if (!producto) return
    const nuevoStock = producto.stock - cantidadComprada
    if (nuevoStock < 0) {
      alert('Stock insuficiente')
      return
    }
    const actualizado = { ...producto, stock: nuevoStock }
    setProducto(actualizado)
    console.log('Stock actualizado:', actualizado)
    // Aquí enviarías la actualización al backend
  }

  return (
    <div>
      <h2>Actualizar Cantidad de Producto</h2>
      <button onClick={handleBuscar}>Buscar producto (mock)</button>
      {producto && (
        <>
          <p>Producto: {producto.nombre}</p>
          <p>Stock actual: {producto.stock}</p>
          <input
            type="number"
            min={1}
            value={cantidadComprada}
            onChange={e => setCantidadComprada(Number(e.target.value))}
          />
          <button onClick={handleActualizar}>Actualizar Stock</button>
        </>
      )}
    </div>
  )
}
