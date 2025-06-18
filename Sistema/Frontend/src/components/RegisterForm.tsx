import React, { useState } from 'react'

export default function RegisterForm() {
  const [form, setForm] = useState({
    rut: '',
    nombre: '',
    apellido: '',
    email: '',
    password: '',
    confirmPassword: ''
  })

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target
    setForm(prev => ({ ...prev, [name]: value }))
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (form.password !== form.confirmPassword) {
      alert('Las contraseñas no coinciden')
      return
    }
    const strongPassword = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!#$%&\/\?¿@])[A-Za-z\d!#$%&\/\?¿@]{8,}$/
    if (!strongPassword.test(form.password)) {
      alert('La contraseña no cumple con los requisitos')
      return
    }
    console.log('Registrando cuenta:', form)
    // Aquí llamarías al backend (Flask) para crear la cuenta
  }

  return (
    <form onSubmit={handleSubmit}>
      <h2>Crear Cuenta</h2>
      <input name="rut" placeholder="RUT" onChange={handleChange} required />
      <input name="nombre" placeholder="Nombre" onChange={handleChange} required />
      <input name="apellido" placeholder="Apellido" onChange={handleChange} required />
      <input name="email" type="email" placeholder="Correo Electrónico" onChange={handleChange} required />
      <input name="password" type="password" placeholder="Contraseña" onChange={handleChange} required />
      <input name="confirmPassword" type="password" placeholder="Confirmar Contraseña" onChange={handleChange} required />
      <button type="submit">Registrar</button>
    </form>
  )
}
