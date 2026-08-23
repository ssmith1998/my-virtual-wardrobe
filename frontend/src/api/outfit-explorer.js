import {post} from './index';

export const outfitRecommendation = async (data) => {
    const response = await post('/outfits/recommend', data, {
        'Content-Type': 'multipart/form-data',
    });
    return response;
    }
