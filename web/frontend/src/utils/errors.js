export function stripHtml(value) {
  if (!value) {
    return ''
  }

  return String(value)
    .replace(/<[^>]*>/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}

export function getFriendlyErrorMessage(error) {
  const data = error.response?.data

  if (error?.name === 'AbortError') {
    return 'Сервис распознавания не отвечает. Запись не создана, попробуйте позже.'
  }

  if (error?.message?.includes('ML warm-up failed')) {
    return 'Сервис распознавания временно недоступен. Запись не создана, попробуйте позже.'
  }

  if (error?.message === 'ML URL is not configured') {
    return 'Адрес сервиса распознавания не настроен.'
  }

  if (!data) {
    return 'Не удалось выполнить запрос. Проверьте подключение к интернету.'
  }

  if (data.user_message) {
    return stripHtml(data.user_message)
  }

  if (data.message === 'No products detected') {
    return 'На фото не удалось распознать продукты. Запись не создана.'
  }

  if (data.message === 'ML service busy') {
    return 'AI-сервис занят. Повторите попытку через несколько секунд.'
  }

  if (data.message === 'ML service connection error') {
    return 'Сервис распознавания запускается или временно недоступен. Запись не создана.'
  }

  if (data.message === 'ML service error') {
    return 'Сервис распознавания временно недоступен. Запись не создана, попробуйте позже.'
  }

  if (error.response?.status === 413) {
    return 'Файл слишком большой. Выберите изображение меньшего размера.'
  }

  if (data.errors) {
    const firstError = Object.values(data.errors).flat()[0]

    if (firstError) {
      return stripHtml(firstError)
    }
  }

  if (data.message) {
    return stripHtml(data.message)
  }

  return 'Произошла ошибка при анализе изображения. Попробуйте ещё раз.'
}

export function getValidationFirstError(error, fallback = 'Проверьте данные.') {
  const errors = error.response?.data?.errors

  if (!errors) {
    return fallback
  }

  const firstError = Object.values(errors).flat()[0]

  return stripHtml(firstError || fallback)
}

export function getLoginErrorMessage(error) {
  if (error.response?.status === 422 || error.response?.status === 401) {
    return 'Неверный email или пароль.'
  }

  return 'Не удалось выполнить вход. Попробуйте позже.'
}

export function getRegisterErrorMessage(error) {
  if (error.response?.data?.errors) {
    return getValidationFirstError(error, 'Проверьте данные регистрации.')
  }

  if (error.response?.status === 422) {
    return 'Проверьте данные регистрации.'
  }

  return 'Не удалось создать аккаунт. Попробуйте позже.'
}