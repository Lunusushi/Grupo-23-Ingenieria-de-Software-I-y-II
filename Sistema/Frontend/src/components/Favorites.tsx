import React, { useState } from 'react'

type Producto = {
  id: string
  nombre: string
  disponible: boolean
}

export default function Favorites() {
  const [favoritos, setFavoritos] = useState<Producto[]>([])
  const [mensaje, setMensaje] = useState('')

  const agregarFavorito = (producto: Producto) => {
    if (favoritos.length >= 30) {
      setMensaje('Límite de 30 productos en favoritos alcanzado')
      return
    }
    if (favoritos.find(p => p.id === producto.id)) {
      setMensaje('Este producto ya está en favoritos')
      return
    }
    setFavoritos([...favoritos, producto])
    setMensaje('')
  }

  const eliminarFavorito = (id: string) => {
    setFavoritos(favoritos.filter(p => p.id !== id))
  }

  const simularCompra = (id: string) => {
    setFavoritos(prev => prev.filter(p => p.id !== id))
    setMensaje('Producto eliminado de favoritos por compra')
  }

  return (
    <div>
      <h2>Favoritos</h2>
      <button onClick={() => agregarFavorito({ id: '1', nombre: 'Cruz plateada', disponible: true })}>
        Agregar Favorito (mock)
      </button>
      {mensaje && <p>{mensaje}</p>}
      <ul>
        {favoritos.map(p => (
          <li key={p.id}>
            {p.nombre}
            <button onClick={() => eliminarFavorito(p.id)}>Eliminar</button>
            <button onClick={() => simularCompra(p.id)}>Simular Compra</button>
          </li>
        ))}
      </ul>
    </div>
  )
}
