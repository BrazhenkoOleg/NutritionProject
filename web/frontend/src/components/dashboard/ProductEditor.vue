<script setup>
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
      query: `${selectedProduct.name_ru} — ${selectedProduct.class_name}`,
    }
  })

  emit('update:editableProducts', nextProducts)
}

function addProductRow() {
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
            @input="updateQuery(index, $event.target.value)"
          />

          <div
            v-if="product.query && !product.class_name"
            class="search-results"
          >
            <button
              v-for="item in getFilteredProducts(product.query)"
              :key="item.class_name"
              type="button"
              class="search-result-item"
              @click="selectProduct(index, item)"
            >
              {{ item.name_ru }} — {{ item.class_name }}
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
            :value="product.weight_g"
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