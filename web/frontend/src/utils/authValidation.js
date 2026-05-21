export function getLoginValidationError(form) {
  if (!form.email || !form.password) {
    return 'Введите email и пароль.'
  }

  return ''
}

export function getRegisterValidationError(form) {
  if (!form.name || !form.email || !form.password || !form.password_confirmation) {
    return 'Заполните все поля.'
  }

  if (form.password.length < 6) {
    return 'Пароль должен быть не короче 6 символов.'
  }

  if (form.password !== form.password_confirmation) {
    return 'Пароли не совпадают.'
  }

  return ''
}