<template>
  <q-card class="recommendation-card">
    <q-img v-if="imageSrc" :src="imageSrc" />
    <q-card-section>
      <div class="text-h6">{{ rec.name || `Recommendation ${idx + 1}` }}</div>
      <div class="text-subtitle2" v-if="meta.category">{{ meta.category }}</div>

      <div class="meta-list">
        <div v-if="meta.colour"><strong>Colour:</strong> {{ meta.colour }}</div>
        <div v-if="meta.pattern"><strong>Pattern:</strong> {{ meta.pattern }}</div>
        <div v-if="meta.material"><strong>Material:</strong> {{ meta.material }}</div>
        <div v-if="meta.style"><strong>Style:</strong> {{ meta.style }}</div>
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  rec: { type: Object, required: true },
  idx: { type: Number, required: true },
});

const meta = computed(() => props.rec.metaData || props.rec.metadata || {});
const imageSrc = computed(() => props.rec.imageUrl || props.rec.image || meta.value.imageUrl || '');
</script>

<style scoped lang="scss">
.recommendation-card { min-height: 120px; }
.recommendation-card q-img { object-fit: cover; }
.meta-list { margin-top: 8px; display: flex; flex-direction: column; gap: 4px; font-size: 0.95rem; }
</style>
