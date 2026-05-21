import api from '../api/axios'

export async function fetchAnalyses() {
  const response = await api.get('/analyses')

  return response.data.data || []
}

export async function analyzeImage(formData) {
  const response = await api.post('/analyze', formData)

  return response.data.data
}

export async function createManualAnalysis(payload) {
  const response = await api.post('/analyses/manual', payload)

  return response.data.data
}

export async function updateAnalysisProducts(analysisId, products) {
  const response = await api.put(`/analyses/${analysisId}/products`, {
    products,
  })

  return response.data.data
}

export async function deleteAnalysis(analysisId) {
  await api.delete(`/analyses/${analysisId}`)
}