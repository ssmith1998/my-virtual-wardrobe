import Router from './index'
import { useAppStore } from '../stores/appStore'

Router.beforeEach((to, from, next) => {
  const appStore = useAppStore()
  if (to.name !== 'Login' && !appStore.isAuthenticated) {
    next({ name: 'Login' })
  }else if (to.name === 'Login' && appStore.isAuthenticated) {
    next({ name: 'Wardrobe' })
  }else {
    next()
  }
})