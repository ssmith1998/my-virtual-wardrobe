import { defineStore } from 'pinia'
import { login } from '../api/requests';
import { openToast } from '../composables/useToast';
import router from '../router';
import { setItem, getItem } from '../composables/useLocalStorage';


export const useAppStore = defineStore('app', {
  state: () => ({
    isAuthenticated: getItem('token') ? true : false,
    user: null,
    token: getItem('token') ?? null,
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
  },
  actions: {
    async loginUser(credentials) {
      try {
            const response = await login(credentials);
            if (response && response.token) {
                this.setToken(response.token);
                this.setAuthenticated(true);
                setItem('token', response.token);
                openToast('Login successful!', 'success');
                router.push({ name: 'wardrobe' });
            }
            return response;
          } catch (error) {
            if(error.response && error.response.status === 401) {
              openToast('Invalid credentials. Please try again.', 'error');
            } else {
              openToast('Error during login: ' + error.message, 'error');
            }
            throw error;
          }
    },
    setAuthenticated(value) {
      this.isAuthenticated = value;
    },
    setUser(user) {
      this.user = user;
    },
    setToken(token) {
      this.token = token;
    }
  },
})  