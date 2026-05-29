<template>
  <q-page>
    <div class="login-form-container q-pa-xl q-gutter-md">
      <form @submit.prevent="handleLogin">
          <q-input label="Username" v-model="username" />
          <q-input label="Password" type="password" v-model="password" />
          <q-btn label="Login" color="primary" class="full-width" type="submit" />
      </form>
    </div>
  </q-page>
</template>

<script setup>
import { ref } from 'vue';
import { login } from '../api/requests';

const username = ref('');
const password = ref('');

const handleLogin = async () => {
  console.log('Attempting login with:', username.value, password.value);
  try {
    await login({ username: username.value, password: password.value });
  } catch (error) {
    console.error('Error during login:', error);
  }
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
