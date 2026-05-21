let unauthorizedHandler = null

export function setUnauthorizedHandler(handler) {
  unauthorizedHandler = handler
}

export function notifyUnauthorized() {
  if (typeof unauthorizedHandler === 'function') {
    unauthorizedHandler()
  }
}