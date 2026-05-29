import Router from './index'
Router.beforeEach((to, from) => {
     console.log('to', to);
     console.log('from', from);
  if (
    to.name !== 'Login'
  ) {
    console.log('Checking authentication for route:', to.name);
    // redirect the user to the login page
    return { name: 'Login' }
  }
})