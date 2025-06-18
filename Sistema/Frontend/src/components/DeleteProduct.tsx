import React, { useState } from 'react'

export default function DeleteProduct() {
  const [idProducto, setIdProducto] = useState('')
  const [confirmacion, setConfirmacion] = useState(false)
  const [mensaje, setMensaje] = useState('')

  const buscarProducto = () => {
    if (idProducto) {
      // En producción, validaría con el backend
      setConfirmacion(true)
    }
  }

  const eliminarProducto = () => {
    setConfirmacion(false)
    setMensaje(`Producto con ID ${idProducto} eliminado.`)
    // Aquí harías la llamada DELETE al backend
  }

  return (
    <div>
      <h2>Eliminar Producto del Catálogo</h2>
      <input
        type="text"
        placeholder="ID del producto"
        value={idProducto}
        onChange={e => setIdProducto(e.target.value)}
      />
      <button onClick={buscarProducto}>Buscar</button>
      {confirmacion && (
        <div>
          <p>¿Estás seguro de que deseas eliminar este producto?</p>
          <button onClick={eliminarProducto}>Sí, eliminar</button>
          <button onClick={() => setConfirmacion(false)}>Cancelar</button>
        </div>
      )}
      {mensaje && <p>{mensaje}</p>}
    </div>
  )
}
