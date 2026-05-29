const routes = [
    {
    'path': '/',
    'component': () => import('layouts/AuthLayout.vue'),
    'children': [
      {
        'path': '',
        'name': 'Login',
        'component': () => import('pages/LoginPage.vue'),
      },
    ],
  },
  {
    path: '/wardrobe',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', name: 'Index', component: () => import('pages/IndexPage.vue') },
      { path: '/hello', name: 'Hello', component: () => import('pages/HelloWorld.vue') }
    ],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
