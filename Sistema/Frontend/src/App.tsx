// src/App.tsx
import React from 'react'
import { BrowserRouter, Routes, Route } from 'react-router-dom'

import Header        from './components/Header'
import HomePage      from './pages/HomePage'
import RegisterPage  from './pages/RegisterPage'
import LoginPage     from './pages/LoginPage'
import FavoritesPage from './pages/FavoritesPage'
import FilterPage    from './pages/FilterPage'
import CartPage      from './pages/CartPage'
import OrderPage     from './pages/OrderPage'
import ProductsPage  from './pages/ProductsPage'
import LocationPage  from './pages/LocationPage'
import EventsPage    from './pages/EventsPage'
import AdminPage     from './pages/AdminPage'

import './index.css'

export default function App() {
  return (
    <BrowserRouter>
      <Header />
      <main style={{ padding: '1rem' }}>
        <Routes>
          <Route path="/"           element={<HomePage />} />
          <Route path="/register"   element={<RegisterPage />} />
          <Route path="/login"      element={<LoginPage />} />
          <Route path="/favorites"  element={<FavoritesPage />} />
          <Route path="/filter"     element={<FilterPage />} />
          <Route path="/cart"       element={<CartPage />} />
          <Route path="/order"      element={<OrderPage />} />
          <Route path="/products"   element={<ProductsPage />} />
          <Route path="/location"   element={<LocationPage />} />
          <Route path="/events"     element={<EventsPage />} />
          <Route path="/admin"      element={<AdminPage />} />
        </Routes>
      </main>
    </BrowserRouter>
  )
}
