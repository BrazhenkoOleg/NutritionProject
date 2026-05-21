import { ref } from 'vue'

export function useTheme() {
  const theme = ref(localStorage.getItem('theme') || 'light')

  function applyTheme(value) {
    theme.value = value
    localStorage.setItem('theme', value)
    document.documentElement.setAttribute('data-theme', value)
  }

  function toggleTheme() {
    applyTheme(theme.value === 'light' ? 'dark' : 'light')
  }

  return {
    theme,
    applyTheme,
    toggleTheme,
  }
}