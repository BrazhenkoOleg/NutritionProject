import { ref } from 'vue'

export function useMealCollapse() {
  const collapsedMeals = ref({
    breakfast: true,
    lunch: true,
    dinner: true,
    snack: true,
  })

  function toggleMeal(mealType) {
    collapsedMeals.value[mealType] = !collapsedMeals.value[mealType]
  }

  function openMeal(mealType) {
    collapsedMeals.value[mealType] = false
  }

  return {
    collapsedMeals,
    toggleMeal,
    openMeal,
  }
}