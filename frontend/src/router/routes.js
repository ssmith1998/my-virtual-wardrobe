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
      { path: '', name: 'wardrobe', component: () => import('pages/WardrobePage.vue') },
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
