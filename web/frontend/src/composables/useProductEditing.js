import { ref } from 'vue'

import { updateAnalysisProducts } from '../services/analysisService'
import { getFriendlyErrorMessage } from '../utils/errors'

import {
  findProductByQuery,
  getProductLabel,
} from '../utils/products'

export function useProductEditing({
  allProducts,
  upsertAnalysis,
  toastStore,
}) {
  const editingAnalysisId = ref(null)
  const editableProducts = ref([])
  const originalEditableProducts = ref([])
  const isSavingProducts = ref(false)

  function startEditProducts(analysis) {
    editingAnalysisId.value = analysis.id

    const productsForEdit = (analysis.products || []).map((product) => {
      const catalogProduct = allProducts.value.find((item) => {
        return item.class_name === product.class_name
      })

      const className = catalogProduct?.class_name || product.class_name || ''
      const name = catalogProduct?.name_ru || product.name_ru || className || 'Продукт'

      return {
        class_name: className,
        query: getProductLabel({
          name_ru: name,
          class_name: className,
        }),
        weight_g: Math.round(Number(product.weight_g || 100)),
      }
    })

    editableProducts.value = productsForEdit
    originalEditableProducts.value = productsForEdit.map((product) => ({ ...product }))
  }

  function cancelEditProducts() {
    editingAnalysisId.value = null
    editableProducts.value = []
    originalEditableProducts.value = []
  }

  async function saveEditedProducts() {
    if (!editingAnalysisId.value) {
      return
    }

    const analysisId = editingAnalysisId.value
    const validProducts = getValidEditableProducts(editableProducts.value)

    if (validProducts.length === 0) {
      toastStore.info('Добавьте хотя бы один продукт и укажите массу.')
      return
    }

    const changeType = getProductsChangeType(
      originalEditableProducts.value,
      editableProducts.value,
    )

    if (changeType === 'none') {
      cancelEditProducts()
      toastStore.info('Изменений нет.')
      return
    }

    isSavingProducts.value = true

    try {
      const updatedAnalysis = await updateAnalysisProducts(
        analysisId,
        validProducts.map((product) => ({
          class_name: product.class_name,
          weight_g: Math.round(Number(product.weight_g || 100)),
        })),
      )

      if (updatedAnalysis) {
        upsertAnalysis(updatedAnalysis)
      }

      cancelEditProducts()

      toastStore.success(getEditSuccessMessage(changeType))
    } catch (error) {
      console.error(error)
      toastStore.error(getFriendlyErrorMessage(error))
    } finally {
      isSavingProducts.value = false
    }
  }

  function formatProductQuery(name, className = '') {
    const cleanName = String(name || '').trim()
    const cleanClassName = String(className || '').trim()

    return cleanName || cleanClassName || ''
  }

  function findProductByQuery(query) {
    const cleanQuery = String(query || '').trim().toLowerCase()

    if (!cleanQuery) {
      return null
    }

    return allProducts.value.find((product) => {
      const name = String(product.name_ru || '').trim().toLowerCase()
      const className = String(product.class_name || '').trim().toLowerCase()

      return cleanQuery === name || cleanQuery === className
    }) || null
  }

  function normalizeEditableProduct(product) {
    const className = String(product.class_name || '').trim()
    const weight = Math.round(Number(product.weight_g || 100))

    if (className) {
      return {
        class_name: className,
        weight_g: weight,
      }
    }

    const matchedProduct = findProductByQuery(product.query)

    if (!matchedProduct?.class_name) {
      return null
    }

    return {
      class_name: matchedProduct.class_name,
      weight_g: weight,
    }
  }

  function getValidEditableProducts(products) {
    return products
      .map((product) => normalizeEditableProduct(product))
      .filter((product) => {
        return product?.class_name && Number(product.weight_g) > 0
      })
  }

  function normalizeProductsForCompare(products) {
    return getValidEditableProducts(products)
      .sort((a, b) => a.class_name.localeCompare(b.class_name))
  }

  function getProductsChangeType(beforeProducts, afterProducts) {
    const before = normalizeProductsForCompare(beforeProducts)
    const after = normalizeProductsForCompare(afterProducts)

    const beforeMap = new Map(before.map((product) => [product.class_name, product.weight_g]))
    const afterMap = new Map(after.map((product) => [product.class_name, product.weight_g]))

    const beforeClasses = [...beforeMap.keys()]
    const afterClasses = [...afterMap.keys()]

    const sameLength = beforeClasses.length === afterClasses.length
    const sameClasses = sameLength && beforeClasses.every((className) => {
      return afterMap.has(className)
    })

    if (!sameClasses) {
      return 'composition'
    }

    const weightChanged = beforeClasses.some((className) => {
      return beforeMap.get(className) !== afterMap.get(className)
    })

    if (weightChanged) {
      return 'weight'
    }

    return 'none'
  }

  function getEditSuccessMessage(changeType) {
    if (changeType === 'composition') {
      return 'Состав продуктов обновлён.'
    }

    if (changeType === 'weight') {
      return 'Вес порции обновлён.'
    }

    return 'Изменения сохранены.'
  }

  return {
    editingAnalysisId,
    editableProducts,
    originalEditableProducts,
    isSavingProducts,

    startEditProducts,
    cancelEditProducts,
    saveEditedProducts,
  }
}