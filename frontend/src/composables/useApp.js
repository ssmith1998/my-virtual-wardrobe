import {removeItem} from './useLocalStorage';

export const logout = () => {
    removeItem('token');
    window.location.href = '/';
}