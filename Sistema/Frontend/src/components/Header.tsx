import React from 'react'
import { Link } from 'react-router-dom'

export default function Header() {
  return (
    <>
      {/* Topbar: buscador + logo + iconos */}
      <div className="topbar">
        <div className="topbar-left">
          <input type="text" placeholder="🔍 Buscar producto…" />
        </div>
        <div className="topbar-center">
          <Link to="/"><h1>Los Cobres</h1></Link>
        </div>
        <div className="topbar-right">
          <Link to="/login"    title="Iniciar sesión">👤</Link>
          <Link to="/register" title="Crear cuenta">➕</Link>
          <Link to="/favorites" title="Favoritos">❤️</Link>
          <Link to="/cart"     title="Carro de compras">🛒</Link>
        </div>
      </div>

      {/* Bottombar: secciones públicas */}
      <nav className="bottombar">
        <Link to="/">Inicio</Link>
        <Link to="/products">Productos</Link>
        <Link to="/location">Ubicación</Link>
        <Link to="/events">Eventos</Link>
      </nav>
    </>
  )
}