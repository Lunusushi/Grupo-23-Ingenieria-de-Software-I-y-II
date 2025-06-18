import React, { useState } from 'react'

const categoriasDisponibles = ['Libros', 'Cuadros', 'Imágenes', 'Joyería', 'Infantil']

export default function CategoryFilter() {
  const [seleccionadas, setSeleccionadas] = useState<string[]>([])

  const toggleCategoria = (categoria: string) => {
    setSeleccionadas(prev =>
      prev.includes(categoria)
        ? prev.filter(c => c !== categoria)
        : prev.length < 5
        ? [...prev, categoria]
        : prev
    )
  }

  const filtrar = () => {
    console.log('Filtrando por categorías:', seleccionadas)
    // Aquí llamarías al backend pasándole el array de categorías
  }

  return (
    <div>
      <h2>Filtrar Catálogo</h2>
      {categoriasDisponibles.map(cat => (
        <label key={cat} style={{ marginRight: '8px' }}>
          <input
            type="checkbox"
            value={cat}
            checked={seleccionadas.includes(cat)}
            onChange={() => toggleCategoria(cat)}
          />
          {cat}
        </label>
      ))}
      <button onClick={filtrar}>Filtrar</button>
    </div>
  )
}
