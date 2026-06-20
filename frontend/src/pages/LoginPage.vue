<template>
  <q-page>
    <div class="login-form-container q-pa-xl q-gutter-md">
      <form @submit.prevent="handleLogin">
          <q-input label="Username" v-model="username" :rules="[val => val.length >= 6 || 'Username is required']" />
          <q-input label="Password" type="password" v-model="password" class="q-mb-md" :rules="[val => val.length >= 6 || 'Password is required']" />
          <q-btn :disabled="!username || !password" label="Login" color="primary" class="full-width" type="submit" :loading="isLoading" />
      </form>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue';
import { useAppStore } from '../stores/appStore';

const appStore = useAppStore();
const username = ref('');
const password = ref('');
const isLoading = ref(false);

const handleLogin = async () => {
  isLoading.value = true;
  try {
    await appStore.loginUser({ email: username.value, password: password.value });
  } catch (error) {
    console.log(error);
    isLoading.value = false;
  } finally {
    isLoading.value = false;
  }
  isLoading.value = false;
};
</script>

<style lang="scss">
  .login-form-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

    form {
      width: 75%;
    }
  }
</style>
