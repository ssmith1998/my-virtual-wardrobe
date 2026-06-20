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
    name: 'Wardrobe',
    component: () => import('layouts/MainLayout.vue'),
    children: [
      { path: '', name: 'Index', component: () => import('pages/IndexPage.vue') },
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
