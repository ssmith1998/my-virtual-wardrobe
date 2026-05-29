import {instance} from './index';

export const login = async () => {
  try {
    const response = await instance.get('/login');
    return response.data;
  } catch (error) {
    console.error('Error fetching login:', error);
    throw error;
  }
}