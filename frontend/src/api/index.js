import axios from 'axios';
import { useAppStore } from '../stores/appStore';
import {logout} from '../composables/useApp';

export const instance = axios.create({
  baseURL: 'https://myvirtualwardrobe.test/api',
  timeout: 3000, // 3 seconds timeout
  headers: {
    'Content-Type': 'application/json',
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


/**
 * Interceptors
 */

instance.interceptors.response.use(
  function (response) {
    console.log('API Response:', response);
    // Any status code that lie within the range of 2xx cause this function to trigger
    // Do something with response data
    return response;
  },
  function (error) {
    console.error('API Error:', error.response ? error.response.data : error.message);
    if (error.response && error.response.status === 401) {
      // Handle 401 Unauthorized error
      logout();
    }
    return Promise.reject(error);
  }
);

instance.interceptors.request.use((config) => {
  const appStore = useAppStore()

  if (appStore.token) {
    config.headers.Authorization = `Bearer ${appStore.token}`
  } else {
    delete config.headers.Authorization
  }

  return config
})