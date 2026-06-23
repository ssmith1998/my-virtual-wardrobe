import axios from 'axios';
import {getItem} from '../composables/useLocalStorage';

export const instance = axios.create({
  baseURL: 'https://myvirtualwardrobe.test/api',
  timeout: 3000, // 3 seconds timeout
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${getItem('token')}`,
  },
});

export const get = async (url) => {
    try {
        const response = await instance.get(url);
        console.log('Response:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
        throw error;
    }
}

export const post = async (url, data, headers) => {
    try {
        const response = await instance.post(url, data, {
            withCredentials: true,
            headers: headers ?? { 'Content-Type': 'application/json' },
        });
        console.log('Response:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
        throw error;
    }
}


export const deleteRequest = async (url, data) => {
    try {
        const response = await instance.delete(url, data, {
            withCredentials: true,
            headers: { 'Content-Type': 'application/json' },
        });
        console.log('Response:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error fetching data:', error);
        throw error;
    }
}