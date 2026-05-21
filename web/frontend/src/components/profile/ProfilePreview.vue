<template>
  <div class="profile-preview-card">
    <div class="preview-ring">
      <strong>{{ targets.kcal }}</strong>
      <span>ккал/день</span>
    </div>

    <div class="preview-macros">
      <div>
        <strong>{{ targets.protein }}</strong>
        <span>белки, г</span>
      </div>

      <div>
        <strong>{{ targets.fat }}</strong>
        <span>жиры, г</span>
      </div>

      <div>
        <strong>{{ targets.carbs }}</strong>
        <span>углеводы, г</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  targets: {
    type: Object,
    default: () => ({
      kcal: 0,
      protein: 0,
      fat: 0,
      carbs: 0,
    }),
  },
})
</script>