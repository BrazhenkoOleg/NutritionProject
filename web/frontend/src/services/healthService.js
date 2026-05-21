import api from '../api/axios'

export async function checkBackendHealth() {
  await api.get('/health')
}