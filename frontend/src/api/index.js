import axios from 'axios';

export const instance = axios.create({
  baseURL: 'https://myvirtualwardrobe.test/api',
  timeout: 1000,
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

export const post = async (url, data) => {
    try {
        const response = await instance.post(url, data, {
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