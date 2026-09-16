/** Defines public pages, role workspaces, lazy loading, and the first client-side access guard. */
import { createRouter, createWebHistory } from 'vue-router'

const Home = () => import('../views/HomeView.vue')
const Cars = () => import('../views/CarsView.vue')
const CarDetail = () => import('../views/CarDetailView.vue')
const Auth = () => import('../views/AuthView.vue')
const PasswordReset = () => import('../views/PasswordResetView.vue')
const Static = () => import('../views/StaticView.vue')
const Legal = () => import('../views/LegalView.vue')
const Workspace = () => import('../views/WorkspaceView.vue')
const PaymentResult = () => import('../views/PaymentResultView.vue')
const OAuthCallback = () => import('../views/OAuthCallbackView.vue')
const CarEditor = () => import('../components/CarEditor.vue')

const workspaceChildren = {
  client: ['dashboard', 'profile', 'reservations', 'reservations/:id', 'payments', 'payments/:id'],
  owner: ['dashboard', 'categories', 'cars', 'cars/:id', 'clients', 'clients/:id', 'reservations', 'reservations/:id', 'payments', 'payments/:id', 'profile'],
  admin: ['dashboard', 'staff', 'staff/create', 'staff/:id', 'clients', 'clients/:id', 'categories', 'cars', 'reservations', 'reservations/:id', 'payments', 'payments/:id', 'settings', 'profile'],
}

const routes = [
  { path: '/', component: Home },
  { path: '/cars', component: Cars },
  { path: '/cars/:id', component: CarDetail },
  { path: '/about', component: Static, props: { page: 'about' } },
  { path: '/contact', component: Static, props: { page: 'contact' } },
  { path: '/privacy', component: Legal, props: { type: 'privacy' } },
  { path: '/legal', component: Legal, props: { type: 'legal' } },
  { path: '/login', component: Auth, props: { mode: 'login' } },
  { path: '/register', component: Auth, props: { mode: 'register' } },
  { path: '/forgot-password', component: PasswordReset },
  { path: '/reset-password', component: PasswordReset },
  { path: '/auth/callback', component: OAuthCallback },
  { path: '/payment/success', component: PaymentResult, props: { state: 'success' } },
  { path: '/payment/cancelled', component: PaymentResult, props: { state: 'cancelled' } },
  { path: '/owner/cars/create', component: CarEditor, props: { role: 'owner' }, meta: { auth: true, roles: ['owner'] } },
  { path: '/owner/cars/:id/edit', component: CarEditor, props: { role: 'owner' }, meta: { auth: true, roles: ['owner'] } },
  ...Object.entries(workspaceChildren).flatMap(([role, paths]) => paths.map(path => ({
    path: `/${role}/${path}`,
    component: Workspace,
    meta: { auth: true, roles: [role] },
  }))),
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

/**
 * Redirects unauthenticated or wrong-role visitors before a protected view opens.
 * Laravel middleware remains the real security boundary for API data.
 */
router.beforeEach(to => {
  if (!to.meta.auth) return true

  const user = JSON.parse(localStorage.getItem('astra_user') || 'null')
  if (!user) return { path: '/login', query: { redirect: to.fullPath } }
  if (!to.meta.roles.includes(user.role)) return `/${user.role}/dashboard`

  return true
})

export default router
