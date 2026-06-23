import {post} from './index';

export const login = async (credentials) => {
    const response = await post('/login', credentials);
    return response;
}
