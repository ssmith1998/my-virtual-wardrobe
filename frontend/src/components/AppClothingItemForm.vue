<template>
    <form @submit.prevent="handleSubmit">
        <q-input
            filled
            label="Item Name"
            v-model="itemName"
            :rules="[val => !!val || 'Item name is required']"
        />
        <q-file
        v-model="itemImage"
        label="Upload Image"
        accept="image/*"
        color="teal"
        flat
        bordered
        style="max-width: 300px"
        />
        <div class="q-mt-md">
            <q-btn color="primary" label="Add Item" type="submit" />
        </div>
    </form>
</template>

<script setup>
import { ref } from 'vue'
import { addClothingItem } from '../api/clothing-item'
import { useClothingItemStore } from 'stores/clothingItemStore';

const emits = defineEmits(['itemAdded']);

const clothingItemStore = useClothingItemStore();
const itemName = ref('')
const itemImage = ref(null) 

const handleSubmit = () => {
    if (!itemName.value) {
        console.error('Item name is required');
        return;
    }

    const formData = new FormData();
    formData.append('name', itemName.value);
    if (itemImage.value) {
        formData.append('image', itemImage.value);
    }
    formData.append('type', 'clothingItem');

    addClothingItem(formData)
        .then(response => {
            console.log('Clothing item added:', response);
            itemName.value = '';
            itemImage.value = null; 
            clothingItemStore.setClothingItems();
            emits('itemAdded');
        })
        .catch(error => {
            console.error('Error adding clothing item:', error);
        });
}
</script>