export function formatNumber(value) {
  return Math.round(Number(value || 0))
}

export function formatWeight(value) {
  return Math.round(Number(value || 0))
}

export function formatCalories(value) {
  return Math.round(Number(value || 0))
}

export function formatMacro(value) {
  return Math.round(Number(value || 0))
}

export function formatMacroPrecise(value) {
  return Number(value || 0).toFixed(1)
}

export function formatPercent(value) {
  return Math.round(Number(value || 0))
}

export function formatTime(value) {
  if (!value) {
    return '--:--'
  }

  return new Date(value).toLocaleTimeString('ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  })
}