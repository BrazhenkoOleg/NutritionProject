export const PENDING_ANALYSIS_TITLES = {
  preparing: 'Подготовка анализа',
  compressing: 'Оптимизируем изображение',
  uploading: 'Загружаем фото',
  recognizing: 'AI распознаёт продукты',
  finalizing: 'Считаем КБЖУ',
  failed: 'Анализ не выполнен',
}

export const PENDING_ANALYSIS_DESCRIPTIONS = {
  preparing: 'Создаём запись и готовим изображение.',
  compressing: 'Уменьшаем размер файла перед отправкой.',
  uploading: 'Передаём изображение в сервис распознавания.',
  recognizing: 'Модель определяет продукты на изображении.',
  finalizing: 'Формируем список продуктов и расчёт питательности.',
  failed: 'Сервис распознавания временно недоступен. Попробуйте ещё раз.',
}

export const PENDING_ANALYSIS_PROGRESS = {
  preparing: 12,
  compressing: 28,
  uploading: 48,
  recognizing: 76,
  finalizing: 94,
  failed: 100,
}

export const PENDING_ANALYSIS_STEP_ORDER = [
  'compressing',
  'uploading',
  'recognizing',
  'finalizing',
]