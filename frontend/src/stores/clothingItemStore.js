import { defineStore } from 'pinia'
import { getClothingItems } from '../api/clothing-item';


export const useClothingItemStore = defineStore('clothingItem', {
  state: () => ({
    clothingItems: [],
  }),

  actions: {
    async setClothingItems() {
      try {
        const response = await getClothingItems();
        this.clothingItems = response;
      } catch (error) {
        console.error('Error fetching clothing items:', error);
      }
    },
  },
});