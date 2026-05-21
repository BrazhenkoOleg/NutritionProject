const ALLOWED_IMAGE_TYPES = [
  'image/jpeg',
  'image/jpg',
  'image/png',
  'image/webp',
]

export function isAllowedImageType(type) {
  return ALLOWED_IMAGE_TYPES.includes(type)
}

export function getCompressedFileName(originalName) {
  const nameWithoutExtension = originalName.replace(/\.[^/.]+$/, '')

  return `${nameWithoutExtension || 'image'}_compressed.jpg`
}

export function compressImage(file, maxSize = 1024, quality = 0.78) {
  return new Promise((resolve, reject) => {
    if (!isAllowedImageType(file.type)) {
      reject(new Error('Поддерживаются только изображения JPG, PNG и WEBP'))
      return
    }

    const reader = new FileReader()

    reader.onload = (event) => {
      const image = new Image()

      image.onload = () => {
        const canvas = document.createElement('canvas')

        let width = image.width
        let height = image.height

        if (width > height && width > maxSize) {
          height = Math.round((height * maxSize) / width)
          width = maxSize
        } else if (height > maxSize) {
          width = Math.round((width * maxSize) / height)
          height = maxSize
        }

        canvas.width = width
        canvas.height = height

        const context = canvas.getContext('2d')

        if (!context) {
          reject(new Error('Не удалось подготовить изображение'))
          return
        }

        context.drawImage(image, 0, 0, width, height)

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error('Не удалось сжать изображение'))
              return
            }

            const compressedFile = new File(
              [blob],
              getCompressedFileName(file.name),
              {
                type: 'image/jpeg',
                lastModified: Date.now(),
              },
            )

            resolve(compressedFile)
          },
          'image/jpeg',
          quality,
        )
      }

      image.onerror = () => {
        reject(new Error('Не удалось прочитать изображение. Выберите JPG, PNG или WEBP.'))
      }

      image.src = event.target.result
    }

    reader.onerror = () => {
      reject(new Error('Не удалось загрузить файл'))
    }

    reader.readAsDataURL(file)
  })
}