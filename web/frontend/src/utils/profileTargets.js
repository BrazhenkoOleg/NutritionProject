import { DEFAULT_PROFILE_FORM } from '../constants/profileOptions'

const ACTIVITY_FACTORS = {
  sedentary: 1.2,
  light: 1.375,
  moderate: 1.55,
  active: 1.725,
  very_active: 1.9,
}

export function createProfileFormFromUser(user) {
  return {
    gender: user?.gender || DEFAULT_PROFILE_FORM.gender,
    age: Number(user?.age || DEFAULT_PROFILE_FORM.age),
    height_cm: Number(user?.height_cm || DEFAULT_PROFILE_FORM.height_cm),
    weight_kg: Number(user?.weight_kg || DEFAULT_PROFILE_FORM.weight_kg),
    activity_level: user?.activity_level || DEFAULT_PROFILE_FORM.activity_level,
    goal: user?.goal || DEFAULT_PROFILE_FORM.goal,
  }
}

export function getCurrentGoalsFromUser(user) {
  return {
    kcal: Math.round(Number(user?.daily_kcal_goal || 0)),
    protein: Math.round(Number(user?.daily_protein_goal || 0)),
    fat: Math.round(Number(user?.daily_fat_goal || 0)),
    carbs: Math.round(Number(user?.daily_carbs_goal || 0)),
  }
}

export function calculatePreviewTargets(form) {
  const weight = Number(form.weight_kg || 0)
  const height = Number(form.height_cm || 0)
  const age = Number(form.age || 0)

  if (!weight || !height || !age) {
    return {
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }
  }

  let bmr = 10 * weight + 6.25 * height - 5 * age

  if (form.gender === 'male') {
    bmr += 5
  } else {
    bmr -= 161
  }

  let kcal = bmr * (ACTIVITY_FACTORS[form.activity_level] || ACTIVITY_FACTORS.moderate)

  if (form.goal === 'lose') {
    kcal *= 0.85
  } else if (form.goal === 'gain') {
    kcal *= 1.1
  }

  const protein = weight * 1.6
  const fat = (kcal * 0.25) / 9
  const carbs = Math.max((kcal - protein * 4 - fat * 9) / 4, 0)

  return {
    kcal: Math.round(kcal),
    protein: Math.round(protein),
    fat: Math.round(fat),
    carbs: Math.round(carbs),
  }
}

export function createProfilePayload(form) {
  return {
    gender: form.gender,
    age: Number(form.age),
    height_cm: Number(form.height_cm),
    weight_kg: Number(form.weight_kg),
    activity_level: form.activity_level,
    goal: form.goal,
  }
}