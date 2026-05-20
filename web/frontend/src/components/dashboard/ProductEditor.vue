<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  editableProducts: {
    type: Array,
    default: () => [],
  },
  allProducts: {
    type: Array,
    default: () => [],
  },
  isLoading: {
    type: Boolean,
    required: true,
  },
})

const emit = defineEmits([
  'update:editableProducts',
  'save',
  'cancel',
])

const activeSearchIndex = ref(null)

function getProductLabel(product) {
  if (!product) {
    return ''
  }

  const name = String(product.name_ru || '').trim()
  const className = String(product.class_name || '').trim()

  return name || className
}

function findProductByClassName(className) {
  if (!className) {
    return null
  }

  return props.allProducts.find((product) => product.class_name === className) || null
}

function normalizeSelectedProducts() {
  let changed = false

  const nextProducts = props.editableProducts.map((product, index) => {
    if (activeSearchIndex.value === index) {
      return product
    }

    if (!product.class_name) {
      return product
    }

    const catalogProduct = findProductByClassName(product.class_name)

    if (!catalogProduct) {
      return product
    }

    const label = getProductLabel(catalogProduct)

    if (product.query === label) {
      return product
    }

    changed = true

    return {
      ...product,
      query: label,
      class_name: catalogProduct.class_name,
    }
  })

  if (changed) {
    emit('update:editableProducts', nextProducts)
  }
}

watch(
  () => props.allProducts,
  normalizeSelectedProducts,
  {
    immediate: true,
    deep: true,
  },
)

watch(
  () => props.editableProducts.length,
  normalizeSelectedProducts,
)

function getFilteredProducts(query) {
  if (!query) {
    return props.allProducts.slice(0, 8)
  }

  const search = query.toLowerCase().trim()

  return props.allProducts
    .filter((product) => {
      return (
        product.name_ru?.toLowerCase().includes(search) ||
        product.class_name?.toLowerCase().includes(search)
      )
    })
    .slice(0, 8)
}

function updateProduct(index, field, value) {
  const nextProducts = props.editableProducts.map((product, productIndex) => {
    if (productIndex !== index) {
      return product
    }

    return {
      ...product,
      [field]: value,
    }
  })

  emit('update:editableProducts', nextProducts)
}

function updateQuery(index, value) {
  activeSearchIndex.value = index

  const nextProducts = props.editableProducts.map((product, productIndex) => {
    if (productIndex !== index) {
      return product
    }

    return {
      ...product,
      query: value,
      class_name: '',
    }
  })

  emit('update:editableProducts', nextProducts)
}

function selectProduct(index, selectedProduct) {
  const nextProducts = props.editableProducts.map((product, productIndex) => {
    if (productIndex !== index) {
      return product
    }

    return {
      ...product,
      class_name: selectedProduct.class_name,
      query: getProductLabel(selectedProduct),
    }
  })

  activeSearchIndex.value = null
  emit('update:editableProducts', nextProducts)
}

function addProductRow() {
  activeSearchIndex.value = props.editableProducts.length

  emit('update:editableProducts', [
    ...props.editableProducts,
    {
      class_name: '',
      query: '',
      weight_g: 100,
    },
  ])
}

function removeProductRow(index) {
  emit(
    'update:editableProducts',
    props.editableProducts.filter((_, productIndex) => productIndex !== index),
  )

  if (activeSearchIndex.value === index) {
    activeSearchIndex.value = null
  }
}

function shouldShowSearchResults(product, index) {
  return (
    activeSearchIndex.value === index &&
    Boolean(product.query) &&
    !product.class_name
  )
}

function handleFocus(index, product) {
  if (!product.class_name) {
    activeSearchIndex.value = index
  }
}

function handleBlur() {
  window.setTimeout(() => {
    activeSearchIndex.value = null
  }, 150)
}
</script>

<template>
  <div class="inline-product-editor">
    <div class="inline-editor-header">
      <div>
        <strong>Редактирование продуктов</strong>
        <span>Измените список продуктов и массу для этой записи</span>
      </div>
    </div>

    <div class="inline-edit-list">
      <div
        v-for="(product, index) in editableProducts"
        :key="index"
        class="inline-edit-row"
      >
        <div class="field search-field">
          <label>Продукт</label>

          <input
            :value="product.query"
            type="text"
            placeholder="Начните вводить название продукта"
            @focus="handleFocus(index, product)"
            @blur="handleBlur"
            @input="updateQuery(index, $event.target.value)"
          />

          <div
            v-if="shouldShowSearchResults(product, index)"
            class="search-results"
          >
            <button
              v-for="item in getFilteredProducts(product.query)"
              :key="item.class_name"
              type="button"
              class="search-result-item"
              @mousedown.prevent="selectProduct(index, item)"
            >
              {{ getProductLabel(item) }}
            </button>

            <p
              v-if="getFilteredProducts(product.query).length === 0"
              class="search-empty"
            >
              Ничего не найдено
            </p>
          </div>
        </div>

        <div class="field weight-field">
          <label>Масса, г</label>

          <input
            :value="Math.round(Number(product.weight_g || 0))"
            type="number"
            min="1"
            step="1"
            @input="updateProduct(index, 'weight_g', Number($event.target.value))"
          />
        </div>

        <button
          type="button"
          class="danger-button"
          @click="removeProductRow(index)"
        >
          Удалить
        </button>
      </div>
    </div>

    <div class="inline-editor-actions">
      <button
        type="button"
        class="secondary-button"
        @click="addProductRow"
      >
        Добавить продукт
      </button>

      <button
        type="button"
        class="primary-button"
        :disabled="isLoading"
        @click="$emit('save')"
      >
        {{ isLoading ? 'Сохраняем...' : 'Сохранить' }}
      </button>

      <button
        type="button"
        class="light-button"
        @click="$emit('cancel')"
      >
        Отмена
      </button>
    </div>
  </div>
</template>