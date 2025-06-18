import React from 'react'

export default function HomePage() {
  return (
    <div>
      {/* Banner de la tienda */}
      <div className="hero" style={{ marginBottom: '1.5rem' }}>
        <img
          src="/Imagenes/front-tienda.jpg"
          alt="Fachada Los Cobres Limitado"
          style={{
            width: '100%',
            height: '250px',
            objectFit: 'cover',
            borderRadius: '8px'
          }}
        />
      </div>

      <h2>Bienvenido a Los Cobres Limitado</h2>
      <p style={{ color: '#c00' }}>
        Selecciona una opción del menú para navegar.
      </p>
    </div>
  )
}
