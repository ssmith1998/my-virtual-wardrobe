<template>
  <q-page>
    <div class="login-form-container q-pa-xl q-gutter-md">
      <form class="full-width">
          <q-input label="Username" />
          <q-input label="Password" type="password" />
          <q-btn label="Login" color="primary" class="full-width" />
        </form>
    </div>
  </q-page>
</template>

<style>
  .login-form-container {
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
  }
</style>
