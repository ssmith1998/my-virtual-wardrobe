import {get, post} from './index';

export const login = async (credentials) => {
  try {
    const response = await post('/login', credentials);
    return response;
  } catch (error) {
    console.error('Error fetching login:', error);
    throw error;
  }
}