export function getProductLabel(product) {
  if (!product) {
    return ''
  }

  const name = String(product.name_ru || '').trim()
  const className = String(product.class_name || '').trim()

  return name || className
}

export function findProductByClassName(products, className) {
  const cleanClassName = String(className || '').trim()

  if (!cleanClassName) {
    return null
  }

  return products.find((product) => {
    return product.class_name === cleanClassName
  }) || null
}

export function findProductByQuery(products, query) {
  const cleanQuery = String(query || '').trim().toLowerCase()

  if (!cleanQuery) {
    return null
  }

  return products.find((product) => {
    const name = String(product.name_ru || '').trim().toLowerCase()
    const className = String(product.class_name || '').trim().toLowerCase()
    const label = getProductLabel(product).toLowerCase()

    return (
      cleanQuery === name ||
      cleanQuery === className ||
      cleanQuery === label
    )
  }) || null
}

export function filterProducts(products, query, limit = 8) {
  const cleanQuery = String(query || '').trim().toLowerCase()

  if (!cleanQuery) {
    return products.slice(0, limit)
  }

  return products
    .filter((product) => {
      const name = String(product.name_ru || '').trim().toLowerCase()
      const className = String(product.class_name || '').trim().toLowerCase()

      return name.includes(cleanQuery) || className.includes(cleanQuery)
    })
    .slice(0, limit)
}

export function createEmptyEditableProduct() {
  return {
    class_name: '',
    query: '',
    weight_g: 100,
  }
}

export function normalizeProductWeight(value) {
  return Math.round(Number(value || 0))
}