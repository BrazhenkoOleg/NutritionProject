export const GENDER_OPTIONS = [
  {
    value: 'male',
    label: 'Мужской',
  },
  {
    value: 'female',
    label: 'Женский',
  },
]

export const ACTIVITY_LEVEL_OPTIONS = [
  {
    value: 'sedentary',
    label: 'Минимальная активность',
  },
  {
    value: 'light',
    label: 'Лёгкая активность',
  },
  {
    value: 'moderate',
    label: 'Средняя активность',
  },
  {
    value: 'active',
    label: 'Высокая активность',
  },
  {
    value: 'very_active',
    label: 'Очень высокая активность',
  },
]

export const GOAL_OPTIONS = [
  {
    value: 'lose',
    label: 'Снижение веса',
  },
  {
    value: 'maintain',
    label: 'Поддержание веса',
  },
  {
    value: 'gain',
    label: 'Набор массы',
  },
]

export const DEFAULT_PROFILE_FORM = {
  gender: 'male',
  age: 22,
  height_cm: 175,
  weight_kg: 70,
  activity_level: 'moderate',
  goal: 'maintain',
}