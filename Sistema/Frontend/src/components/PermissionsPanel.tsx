import React, { useState } from 'react'

type User = {
  id: string
  nombre: string
  roles: string[]
}

export default function PermissionsPanel() {
  // Usuario actual (mock)
  const currentUser = { id: '0', nombre: 'Usuario Admin', roles: ['admin'] }

  if (!currentUser.roles.includes('admin') && !currentUser.roles.includes('caja')) {
    return <p>No tienes permisos para gestionar usuarios.</p>
  }

  const [users, setUsers] = useState<User[]>([
    { id: '1', nombre: 'María Pérez', roles: ['cliente'] },
    { id: '2', nombre: 'Juan Gómez', roles: ['cliente', 'caja'] },
    { id: '3', nombre: 'Ana Díaz', roles: ['admin'] }
  ])

  const toggleRole = (userId: string, role: string) => {
    setUsers(prev =>
      prev.map(user =>
        user.id === userId
          ? {
              ...user,
              roles: user.roles.includes(role)
                ? user.roles.filter(r => r !== role)
                : [...user.roles, role]
            }
          : user
      )
    )
  }

  const handleSave = (userId: string) => {
    const user = users.find(u => u.id === userId)
    console.log('Permisos actualizados para usuario:', user)
    alert(`Permisos actualizados para ${user?.nombre}`)
    // Aquí llamarías al backend para persistir cambios
  }

  return (
    <div>
      <h2>Panel de Permisos de Usuario</h2>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Admin</th>
            <th>Caja</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          {users.map(user => (
            <tr key={user.id}>
              <td>{user.nombre}</td>
              <td>
                <input
                  type="checkbox"
                  checked={user.roles.includes('admin')}
                  onChange={() => toggleRole(user.id, 'admin')}
                />
              </td>
              <td>
                <input
                  type="checkbox"
                  checked={user.roles.includes('caja')}
                  onChange={() => toggleRole(user.id, 'caja')}
                />
              </td>
              <td>
                <button onClick={() => handleSave(user.id)}>Guardar</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
