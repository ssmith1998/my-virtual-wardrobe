<template>
  <div class="results-wrapper">
    <div class="results-header">
      <q-btn flat color="primary" label="Regenerate outfit" @click="$emit('back')" />
    </div>

    <div v-if="isLoading" class="loading-container">
      <q-spinner-dots size="64" color="primary" />
      <div class="loading-text">Generating recommendations…</div>
    </div>

    <div v-else class="cards-grid">
      <AppRecommendationItem v-for="(rec, idx) in recommendations" :key="idx" :rec="rec" :idx="idx" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  recommendations: { type: Array, default: () => [] },
  isLoading: { type: Boolean, default: false },
});

defineEmits(['back']);

import AppRecommendationItem from './AppRecommendationItem.vue';
</script>

<style scoped lang="scss">
.results-wrapper {
  width: 100%;
  padding: 0 16px;
  box-sizing: border-box;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 240px;
}

.loading-text { margin-top: 12px; }

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
  margin-top: 12px;
}

.recommendation-card { min-height: 120px; }

.recommendation-card q-img { object-fit: cover; }

.meta-list {
  margin-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  font-size: 0.95rem;
}
</style>
