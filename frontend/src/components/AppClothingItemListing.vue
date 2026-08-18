<template>
    <div class="q-pa-md">
        <div v-if="wardrobeItems.length > 0" class="wardrobe-item-list row q-col-gutter-md">
            <div v-for="item in wardrobeItems" :key="item.id" class="col-12 col-sm-6 col-md-4 col-lg-3 h-100">
                 <q-card class="wardrobe-item-card">
                    <img :src="item.imageUrl" alt="Clothing Item Image" class="wardrobe-item-image" />
                    <q-card-section>
                    {{ item.name }}
                    </q-card-section>
                </q-card>
            </div>
        </div>
        <div v-else>
            <p>No clothing items found. Use the plus button at the bottom of the screen to add some clothes to your wardrobe!</p> 
        </div>
    </div>          
</template>
<script setup>
    import { useClothingItemStore } from 'stores/clothingItemStore';
    const clothingItemStore = useClothingItemStore();
    import { ref, watch } from 'vue';

    const wardrobeItems = ref([]);

    clothingItemStore.setClothingItems();

    watch(() => clothingItemStore.clothingItems, (newItems) => {
        wardrobeItems.value = newItems;
    }, { immediate: true });


</script>
<style>
    .wardrobe-item-list {
        padding: 16px;
    }
    .wardrobe-item-card {
        height: 100%;
    }
    .wardrobe-item-image {
        width: 100%;
        height: 20rem;
        object-fit: cover;
    }
</style>