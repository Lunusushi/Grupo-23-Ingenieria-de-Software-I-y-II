import React, { useState } from 'react'

export default function OrderVerification() {
  const [codigo, setCodigo] = useState('')
  const [verificado, setVerificado] = useState(false)
  const [mensaje, setMensaje] = useState('')

  const verificarCodigo = () => {
    // En producción, llamarías al backend con el código
    if (codigo === 'AB12') {
      setVerificado(true)
      setMensaje('Pedido verificado correctamente.')
    } else {
      setVerificado(false)
      setMensaje('Código no encontrado.')
    }
  }

  return (
    <div>
      <h2>Verificación de Pedido</h2>
      <input
        type="text"
        placeholder="Código de Retiro"
        value={codigo}
        onChange={e => setCodigo(e.target.value)}
      />
      <button onClick={verificarCodigo}>Verificar</button>
      <p>{mensaje}</p>
      {verificado && <div>Desplegando detalles del pedido (mock)...</div>}
    </div>
  )
}
