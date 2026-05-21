<template>
  <form
    class="profile-form"
    @submit.prevent="$emit('submit')"
  >
    <div class="profile-grid">
      <div class="form-group">
        <label>Пол</label>

        <select v-model="localForm.gender">
          <option
            v-for="option in genderOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="form-group">
        <label>Возраст</label>

        <input
          v-model.number="localForm.age"
          type="number"
          min="14"
          max="100"
          placeholder="Например, 22"
        />
      </div>

      <div class="form-group">
        <label>Рост, см</label>

        <input
          v-model.number="localForm.height_cm"
          type="number"
          min="120"
          max="230"
          step="0.1"
          placeholder="Например, 178"
        />
      </div>

      <div class="form-group">
        <label>Вес, кг</label>

        <input
          v-model.number="localForm.weight_kg"
          type="number"
          min="35"
          max="250"
          step="0.1"
          placeholder="Например, 72"
        />
      </div>

      <div class="form-group profile-grid-wide">
        <label>Активность</label>

        <select v-model="localForm.activity_level">
          <option
            v-for="option in activityLevelOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>

      <div class="form-group profile-grid-wide">
        <label>Цель</label>

        <select v-model="localForm.goal">
          <option
            v-for="option in goalOptions"
            :key="option.value"
            :value="option.value"
          >
            {{ option.label }}
          </option>
        </select>
      </div>
    </div>

    <slot />
  </form>
</template>

<script setup>
import { reactive, watch } from 'vue'

import {
  ACTIVITY_LEVEL_OPTIONS,
  GENDER_OPTIONS,
  GOAL_OPTIONS,
} from '../../constants/profileOptions'

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits([
  'submit',
  'update:form',
])

const genderOptions = GENDER_OPTIONS
const activityLevelOptions = ACTIVITY_LEVEL_OPTIONS
const goalOptions = GOAL_OPTIONS

const localForm = reactive({
  gender: '',
  age: null,
  height_cm: null,
  weight_kg: null,
  activity_level: '',
  goal: '',
})

watch(
  () => props.form,
  (value) => {
    Object.assign(localForm, value)
  },
  {
    immediate: true,
    deep: true,
  },
)

watch(
  localForm,
  (value) => {
    emit('update:form', { ...value })
  },
  { deep: true },
)
</script>