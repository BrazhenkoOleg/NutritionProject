export function getPreviewImageUrl(analysis) {
  const imageUrl = normalizeImageUrl(analysis?.image_url)

  if (!imageUrl) {
    return null
  }

  if (isPreviewImageUrl(imageUrl)) {
    return imageUrl
  }

  return getAnalysisImageUrl(analysis)
}

export function getAnalysisImageUrl(analysis) {
  const imageUrl = normalizeImageUrl(analysis?.image_url)

  if (!imageUrl) {
    return null
  }

  if (isOldStorageImageUrl(imageUrl)) {
    return null
  }

  return imageUrl
}

function normalizeImageUrl(value) {
  return String(value || '').trim()
}

function isPreviewImageUrl(url) {
  return url.startsWith('blob:') || url.startsWith('data:')
}

function isOldStorageImageUrl(url) {
  return url.includes('/storage/')
}