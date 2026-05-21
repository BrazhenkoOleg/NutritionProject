import api from '../api/axios'

export async function fetchCurrentUser() {
  const response = await api.get('/user')

  return response.data.user
}

export async function loginUser(payload) {
  const response = await api.post('/login', {
    email: payload.email,
    password: payload.password,
  })

  return response.data
}

export async function registerUser(payload) {
  const response = await api.post('/register', {
    name: payload.name,
    email: payload.email,
    password: payload.password,
    password_confirmation: payload.password_confirmation,
  })

  return response.data
}

export async function updateUserProfile(payload) {
  const response = await api.put('/profile', payload)

  return response.data
}

export async function logoutUser() {
  await api.post('/logout')
}