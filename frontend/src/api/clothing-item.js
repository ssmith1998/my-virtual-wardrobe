import {post, deleteRequest, get} from './index';

export const addClothingItem = async (clothingItem) => {
    if (clothingItem instanceof FormData) {
        const response = await post('/clothing-items', clothingItem, {
            'Content-Type': 'multipart/form-data',
        });
        return response;
    }
    const response = await post('/clothing-items', clothingItem);
    return response;
}

export const getClothingItems = async () => {
    const response = await get('/wardrobe');
    return response;
}

export const getClothingItem = async (id) => {
    const response = await get(`/clothing-items/${id}`);
    return response;
}

export const removeClothingItem = async (id) => {
    const response = await deleteRequest(`/clothing-items/${id}`);
    return response;
}
