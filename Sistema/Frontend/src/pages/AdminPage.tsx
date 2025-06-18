import React from 'react'
import StockUpdate from '../components/StockUpdate'
import AddProduct from '../components/AddProduct'
import DeleteProduct from '../components/DeleteProduct'
import PermissionsPanel from '../components/PermissionsPanel'

export default function AdminPage() {
  return (
    <div className="sections">
      <section className="card"><h3>Actualizar Stock</h3><StockUpdate/></section>
      <section className="card"><h3>Añadir Producto</h3><AddProduct/></section>
      <section className="card"><h3>Eliminar Producto</h3><DeleteProduct/></section>
      <section className="card full-width"><h3>Permisos de Usuario</h3><PermissionsPanel/></section>
    </div>
  )
}
