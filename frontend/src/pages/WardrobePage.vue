<template>
  <q-page>
    <AppClothingItemListing />
    <div class="top absolute-bottom text-center">
      <q-btn color="blue" text-color="white" round icon="add" size="lg" class="q-mb-md" @click="openDialog = !openDialog"/>
    </div>
    <AppDialog v-model="openDialog" :maximized="true">
        <template #header>
          <div class="text-h6">Add New Item</div>
        </template>
        <template #body>
          <AppClothingItemForm @itemAdded="handleItemAdded" />
        </template>
      </AppDialog>
  </q-page>
</template>

<script setup>
import AppDialog from 'components/AppDialog.vue'
import AppClothingItemForm from 'components/AppClothingItemForm.vue'
import AppClothingItemListing from 'components/AppClothingItemListing.vue'
import { openToast} from 'src/composables/useToast.js'
import { ref } from 'vue'
 const openDialog = ref(false)

const handleItemAdded = () => {
  openDialog.value = false;
  openToast('Item added successfully!');
}
</script>
