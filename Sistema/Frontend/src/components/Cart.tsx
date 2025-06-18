import React, { useState } from 'react'

type Producto = {
  id: string
  nombre: string
  precio: number
}

type ItemCarrito = Producto & { cantidad: number }

export default function Cart() {
  const [carrito, setCarrito] = useState<ItemCarrito[]>([])

  const agregarProducto = (producto: Producto) => {
    if (carrito.length >= 10) {
      alert('Límite de 10 productos alcanzado')
      return
    }
    setCarrito(prev => {
      const existente = prev.find(p => p.id === producto.id)
      if (existente) {
        return prev.map(p =>
          p.id === producto.id ? { ...p, cantidad: p.cantidad + 1 } : p
        )
      }
      return [...prev, { ...producto, cantidad: 1 }]
    })
  }

  const eliminarProducto = (id: string) => {
    setCarrito(prev => prev.filter(p => p.id !== id))
  }

  const vaciarCarrito = () => setCarrito([])

  const total = carrito.reduce((sum, p) => sum + p.precio * p.cantidad, 0)

  return (
    <div>
      <h2>Carro de Compras</h2>
      <button onClick={() => agregarProducto({ id: 'prod1', nombre: 'Biblia pequeña', precio: 12000 })}>
        Agregar Producto (mock)
      </button>
      <ul>
        {carrito.map(p => (
          <li key={p.id}>
            {p.nombre} x {p.cantidad} — ${p.precio * p.cantidad}
            <button onClick={() => eliminarProducto(p.id)}>Eliminar</button>
          </li>
        ))}
      </ul>
      <p>Total: ${total}</p>
      <button onClick={vaciarCarrito}>Vaciar Carrito</button>
    </div>
  )
}
