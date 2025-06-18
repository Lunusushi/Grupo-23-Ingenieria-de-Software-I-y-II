import React, { useState } from 'react'

export default function AddProduct() {
  const [producto, setProducto] = useState({
    nombre: '',
    descripcion: '',
    precio: '',
    stock: '',
    categoria: '',
    sku: ''
  })

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target
    setProducto(prev => ({ ...prev, [name]: value }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    console.log('Producto agregado:', producto)
    // Aquí enviarías el nuevo producto al backend
  }

  return (
    <form onSubmit={handleSubmit}>
      <h2>Añadir Producto</h2>
      <input name="nombre" placeholder="Nombre" onChange={handleChange} required />
      <input name="descripcion" placeholder="Descripción" onChange={handleChange} required />
      <input name="precio" type="number" placeholder="Precio" onChange={handleChange} required />
      <input name="stock" type="number" placeholder="Stock" onChange={handleChange} required />
      <input name="categoria" placeholder="Categoría" onChange={handleChange} required />
      <input name="sku" placeholder="SKU" onChange={handleChange} required />
      <button type="submit">Agregar</button>
    </form>
  )
}
