<template>
    <q-dialog v-model="modelValue" :maximized="maximized">
        <q-card>
            <q-bar v-if="maximized">
                <q-space />
                <q-btn dense flat icon="close" @click="emit('update:modelValue', false)" v-if="maximized">
                    <q-tooltip class="bg-white text-primary">Close</q-tooltip>
                </q-btn>
            </q-bar>
            <q-card-section>
                <slot name="header"></slot>
            </q-card-section>
            <q-card-section>
                <slot name="body"></slot>
            </q-card-section>
            <q-card-section>
                <slot name="footer"></slot>
            </q-card-section>
            </q-card>
    </q-dialog>
</template>

<script setup>
import { toRefs } from 'vue'
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  maximized: {
    type: Boolean,
    default: false,
  },
})

const { modelValue, maximized } = toRefs(props);

const emit = defineEmits(['update:modelValue', 'update:maximized']);
</script>