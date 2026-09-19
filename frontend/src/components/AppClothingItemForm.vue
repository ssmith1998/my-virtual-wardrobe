<template>
  <q-card flat bordered class="form-card">
    <q-card-section class="q-pa-lg">
      <div class="row items-center no-wrap q-mb-lg">
        <div class="icon-wrap q-mr-md">
          <q-icon name="checkroom" size="28px" color="primary" />
        </div>
        <div>
          <div class="text-h6 text-weight-medium">Add clothing item</div>
          <div class="text-caption text-grey-7">Save a new piece to your wardrobe.</div>
        </div>
      </div>

      <q-form class="q-gutter-md" @submit.prevent="handleSubmit">
        <q-input
          v-model="itemName"
          outlined
          label="Item name"
          placeholder="e.g. Black wool coat"
          color="primary"
          maxlength="120"
          :rules="[(val) => !!val?.trim() || 'Item name is required']"
          hide-bottom-space
        />

        <q-file
          v-model="itemImage"
          label="Upload image"
          accept="image/*"
          outlined
          color="primary"
          max-files="1"
          counter
          clearable
          class="file-input"
        />

        <div v-if="itemImage" class="image-preview">
          <q-img :src="imagePreviewUrl" fit="cover" class="rounded-borders" />
        </div>

        <div class="row justify-end q-pt-sm">
          <q-btn
            type="submit"
            color="primary"
            icon="add"
            label="Add item"
            :loading="isSubmitting"
            :disable="!itemName.trim() || !itemImage || isSubmitting"
            class="submit-btn"
          />
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'
import { addClothingItem } from '../api/clothing-item'
import { useClothingItemStore } from 'stores/clothingItemStore'

const emits = defineEmits(['itemAdded'])

const clothingItemStore = useClothingItemStore()
const itemName = ref('')
const itemImage = ref(null)
const isSubmitting = ref(false)
const imageObjectUrl = ref('')
const imagePreviewUrl = ref('')

watch(
  () => itemImage.value,
  (newValue, oldValue) => {
    if (oldValue && oldValue instanceof File && imageObjectUrl.value) {
      URL.revokeObjectURL(imageObjectUrl.value)
    }

    if (!newValue) {
      imageObjectUrl.value = ''
      imagePreviewUrl.value = ''
      return
    }

    if (typeof newValue === 'string') {
      imageObjectUrl.value = ''
      imagePreviewUrl.value = newValue
      return
    }

    if (newValue instanceof File) {
      imageObjectUrl.value = URL.createObjectURL(newValue)
      imagePreviewUrl.value = imageObjectUrl.value
    }
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  if (imageObjectUrl.value) {
    URL.revokeObjectURL(imageObjectUrl.value)
  }
})

const handleSubmit = () => {
  if (!itemName.value.trim()) {
    console.error('Item name is required')
    return
  }

  isSubmitting.value = true

  const formData = new FormData()
  formData.append('name', itemName.value.trim())

  if (itemImage.value) {
    formData.append('image', itemImage.value)
  }

  formData.append('type', 'clothingItem')

  addClothingItem(formData)
    .then((response) => {
      console.log('Clothing item added:', response)
      itemName.value = ''
      itemImage.value = null
      clothingItemStore.setClothingItems()
      emits('itemAdded')
    })
    .catch((error) => {
      console.error('Error adding clothing item:', error)
    })
    .finally(() => {
      isSubmitting.value = false
    })
}
</script>

<style scoped>
.form-card {
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.72);
  backdrop-filter: blur(10px);
}

.icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(103, 80, 164, 0.1);
}

.image-preview {
  max-width: 280px;
  overflow: hidden;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.08);
}

.file-input {
  max-width: 420px;
}

.submit-btn {
  min-width: 150px;
}
</style>