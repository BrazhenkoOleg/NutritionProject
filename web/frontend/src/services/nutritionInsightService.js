import api from '../api/axios'

export async function fetchWeeklyNutritionInsight(date) {
  const response = await api.get('/nutrition/weekly-insight', {
    params: {
      date,
    },
  })

  return response.data.insight
}