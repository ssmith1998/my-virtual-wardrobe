import Router from './index'
import { useAppStore } from '../stores/appStore'

Router.beforeEach((to) => {
  const appStore = useAppStore()
  if (to.name !== 'Login' && !appStore.isAuthenticated) {
    return { name: 'Login' }
  }else if (to.name === 'Login' && appStore.isAuthenticated) {
    return { name: 'Wardrobe' }
  }else {
    return true;
  }
})