<!-- Shared authenticated workspace shell for client, Responsable, and administrator pages. -->
<script setup>
import { ref, computed, markRaw, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import {
  LayoutDashboard, Car, CalendarCheck, Users,
  CreditCard, Settings, LogOut, Search, Bell, Menu, X, Shield,
  HelpCircle, Copy
} from 'lucide-vue-next'

import DashboardHome from '../components/admin/DashboardHome.vue'
import ClientDashboard from '../components/client/ClientDashboard.vue'
import AstraLogo from '../components/AstraLogo.vue'
import CarManager from '../components/admin/CarManager.vue'
import ReservationManager from '../components/admin/ReservationManager.vue'
import ClientManager from '../components/admin/ClientManager.vue'
import PaymentManager from '../components/admin/PaymentManager.vue'
import SettingsManager from '../components/admin/SettingsManager.vue'
import StaffManager from '../components/admin/StaffManager.vue'
import CategoryManager from '../components/admin/CategoryManager.vue'
import api from '../lib/api'
import { publicMediaUrl } from '../lib/media'
import { getEcho } from '../lib/realtime'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const sidebarExpanded = ref(false)
const workspaceSearch = ref('')
const notifications = ref([])
const notificationsOpen = ref(false)
const copied = ref(false)
const currentUserId = auth.user?.id
const failedAvatarUrl = ref('')
const avatarUrl = computed(() => publicMediaUrl(auth.user?.avatar_path))
const avatarAvailable = computed(() => Boolean(avatarUrl.value) && failedAvatarUrl.value !== avatarUrl.value)
/** Records a failed avatar URL so the initials fallback can be shown. */
function markAvatarFailed(event) { failedAvatarUrl.value = event.currentTarget.currentSrc || event.currentTarget.src }
const roleLabel = computed(() => ({
  client: 'Espace Client',
  owner: 'Espace Responsable ASTRA',
  admin: 'Espace Administrateur',
}[auth.user?.role] || 'Accès non autorisé'))

/** Reads the active workspace section from the current route path. */
const getInitialTab = () => {
  return window.location.pathname.split('/').filter(Boolean)[1] || 'dashboard'
}
const activeTab = ref(getInitialTab())

watch(() => route.path, () => {
  activeTab.value = getInitialTab()
}, { immediate: true })

const navItems = computed(() => {
  const role = auth.user?.role
  const items = [{ id: 'dashboard', label: 'Vue d\'ensemble', icon: LayoutDashboard, component: role === 'client' ? ClientDashboard : DashboardHome }]
  if (role !== 'client') items.push({ id: 'categories', label: 'Catégories', icon: Menu, component: CategoryManager }, { id: 'cars', label: 'Véhicules', icon: Car, component: CarManager })
  items.push({ id: 'reservations', label: 'Réservations', icon: CalendarCheck, component: ReservationManager }, { id: 'payments', label: 'Paiements', icon: CreditCard, component: PaymentManager })
  if (role === 'admin' || role === 'owner') items.push({ id: 'clients', label: 'Clients', icon: Users, component: ClientManager })
  if (role === 'admin') {
    items.push({ id: 'staff', label: 'Équipe', icon: Shield, component: StaffManager })
  }
  if (role === 'admin') items.push({ id: 'settings', label: 'Paramètres', icon: Settings, component: SettingsManager })
  else items.push({ id: 'profile', label: 'Mon profil', icon: Settings, component: SettingsManager })
  return items
})

const activeComponent = computed(() => {
  const item = navItems.value.find(i => i.id === activeTab.value)
  return item ? markRaw(item.component) : null
})

const pageTitle = computed(() => {
  const item = navItems.value.find(i => i.id === activeTab.value)
  return item ? item.label : 'Dashboard'
})

/** Ends the API session and returns the visitor to login. */
async function logout() {
  await auth.logout()
  router.push('/login')
}

/** Opens one section inside the current role workspace. */
function openItem(id) { router.push(`/${auth.user.role}/${id}`) }
/** Loads the authenticated user's private notification feed. */
async function loadNotifications() {
  try {
    const result = await api.get('/notifications')
    notifications.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
  } catch {}
}
const unreadCount = computed(() => notifications.value.filter(notification => !notification.read_at).length)
/** Persists the read-all action and updates the local feed immediately. */
async function readAll() {
  await api.patch('/notifications/read-all')
  notifications.value.forEach(notification => { notification.read_at = new Date().toISOString() })
}
/** Copies the current workspace URL and briefly shows confirmation. */
async function copyUrl() {
  await navigator.clipboard.writeText(window.location.href)
  copied.value = true
  setTimeout(() => { copied.value = false }, 1500)
}
/** Sends the top-bar fleet search to the public catalogue. */
function submitSearch() {
  if (workspaceSearch.value.trim()) router.push({ path: '/cars', query: { search: workspaceSearch.value.trim() } })
}
let channel
onMounted(() => {
  loadNotifications()
  const echo = getEcho()
  if (echo && currentUserId) {
    channel = echo.private(`users.${currentUserId}`)
    channel.listen('.notification.created', event => notifications.value.unshift(event.notification))
  }
})
onUnmounted(() => {
  if (channel && currentUserId) getEcho()?.leave(`users.${currentUserId}`)
})
</script>

<template>
  <div class="ws">
    <!-- Icon Sidebar -->
    <aside class="ws-side">
      <!-- Logo -->
      <RouterLink to="/" class="ws-side__logo" aria-label="Accueil ASTRA">
        <AstraLogo class="ws-side__logo-image" />
      </RouterLink>

      <!-- Main Nav Icons -->
      <nav class="ws-side__nav">
        <button v-for="item in navItems" :key="item.id"
                @click="openItem(item.id)"
                :class="['ws-side__btn', { 'ws-side__btn--active': activeTab === item.id }]"
                :title="item.label">
          <component :is="item.icon" :size="20" />
        </button>
      </nav>

      <!-- Bottom Icons -->
      <div class="ws-side__bottom">
        <button class="ws-side__btn" title="Aide" @click="router.push('/contact')"><HelpCircle :size="20" /></button>
        <button class="ws-side__btn" title="Déconnexion" @click="logout"><LogOut :size="20" /></button>
      </div>
    </aside>

    <!-- Main Container -->
    <div class="ws-main">
      <div class="ws-container">
        <!-- Top Bar -->
        <header class="ws-topbar">
          <div class="ws-topbar__left">
            <div>
              <span class="ws-topbar__role">{{ roleLabel }}</span>
              <h1 class="ws-topbar__title">{{ pageTitle }}</h1>
            </div>
            <div class="ws-topbar__icons">
              <button @click="copyUrl" :title="copied ? 'Lien copié' : 'Copier le lien'"><Copy :size="16" /></button>
              <button @click="router.push('/contact')" title="Aide"><HelpCircle :size="16" /></button>
            </div>
          </div>

          <div class="ws-topbar__center">
            <div class="ws-topbar__search">
              <Search :size="15" />
              <input v-model="workspaceSearch" @keyup.enter="submitSearch" type="search" placeholder="Rechercher la flotte..." />
            </div>
          </div>

          <div class="ws-topbar__right">
            <button class="ws-topbar__notif" @click="notificationsOpen=!notificationsOpen" aria-label="Notifications">
              <Bell :size="18" />
              <span v-if="unreadCount" class="ws-topbar__notif-dot"></span>
            </button>
            <div class="ws-topbar__avatar" @click="openItem(auth.user.role==='admin'?'settings':'profile')">
              <img v-if="avatarAvailable" :src="avatarUrl" alt="Photo de profil" @error="markAvatarFailed" />
              <template v-else>{{ auth.user?.first_name?.[0] }}{{ auth.user?.last_name?.[0] }}</template>
            </div>
          </div>
          <div v-if="notificationsOpen" class="ws-notifications"><div class="ws-notifications__head"><strong>Notifications ({{unreadCount}})</strong><button @click="readAll">Tout lire</button></div><button v-for="notification in notifications.slice(0,8)" :key="notification.id" class="ws-notification" :class="{'ws-notification--unread':!notification.read_at}"><strong>{{notification.title}}</strong><span>{{notification.message}}</span></button><p v-if="!notifications.length">Aucune notification.</p></div>
        </header>

        <!-- Sub Navigation -->
        <div class="ws-subnav">
          <div class="ws-subnav__tabs">
            <button v-for="item in navItems.slice(0, 4)" :key="item.id"
                    :class="['ws-subnav__tab', { 'ws-subnav__tab--active': activeTab === item.id }]"
                    @click="openItem(item.id)">
              {{ item.label }}
            </button>
          </div>
          <RouterLink to="/" class="ws-subnav__site">Voir le site →</RouterLink>
        </div>

        <!-- Content Canvas -->
        <main class="ws-content">
          <component :is="activeComponent" />
        </main>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ws {
  display: flex;
  height: 100vh;
  background: #ece8f4;
  font-family: 'Inter', system-ui, sans-serif;
  overflow: hidden;
}

/* ═══════ SIDEBAR ═══════ */
.ws-side {
  width: 68px;
  background: #ece8f4;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.25rem 0;
  gap: 0.25rem;
  flex-shrink: 0;
}
.ws-side__logo {
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}
.ws-side__logo-image {
  display: block;
  width: 48px;
  height: auto;
  object-fit: contain;
}
.ws-side__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}
.ws-side__btn {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  border: none;
  background: transparent;
  color: #9690a8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.ws-side__btn:hover {
  background: rgba(0,0,0,0.06);
  color: #4a3f6b;
}
.ws-side__btn--active {
  background: #fff;
  color: #111;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.ws-side__bottom {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  margin-top: auto;
}

/* ═══════ MAIN CONTAINER ═══════ */
.ws-main {
  flex: 1;
  padding: 0.75rem 0.75rem 0.75rem 0;
  overflow: hidden;
  min-width: 0;
}
.ws-container {
  background: #fff;
  border-radius: 1.5rem;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 4px 24px rgba(0,0,0,0.04);
}

/* ═══════ TOP BAR ═══════ */
.ws-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 2rem;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
.ws-topbar__left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.ws-topbar__title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #111;
  letter-spacing: -0.02em;
}
.ws-topbar__role {
  display: block;
  margin-bottom: 0.15rem;
  color: #7666a8;
  font-size: 0.62rem;
  font-weight: 900;
  letter-spacing: 0.11em;
  line-height: 1;
  text-transform: uppercase;
}
.ws-topbar__icons {
  display: flex;
  gap: 0.5rem;
  color: #c0bcc8;
}
.ws-topbar__center {
  flex: 1;
  max-width: 340px;
  margin: 0 2rem;
}
.ws-topbar__search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f7f7f8;
  border: 1px solid #efefef;
  border-radius: 100px;
  padding: 0.5rem 1rem;
  color: #b0adb8;
}
.ws-topbar__search input {
  border: none;
  outline: none;
  background: transparent;
  font-size: 0.8rem;
  font-weight: 500;
  color: #111;
  width: 100%;
}
.ws-topbar__search input::placeholder {
  color: #b0adb8;
}
.ws-topbar__right {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.ws-notifications { position:absolute; z-index:40; top:72px; right:28px; width:min(360px,calc(100vw - 80px)); max-height:420px; overflow:auto; background:#fff; border:1px solid #ececec; border-radius:18px; box-shadow:0 18px 45px rgba(0,0,0,.14); padding:.65rem; }
.ws-notifications__head { display:flex; justify-content:space-between; padding:.65rem; font-size:.78rem; }
.ws-notifications__head button { border:0; background:none; font-weight:700; cursor:pointer; color:#6d28d9; }
.ws-notification { display:flex; flex-direction:column; gap:.25rem; width:100%; border:0; border-radius:12px; background:#fff; padding:.75rem; text-align:left; cursor:pointer; }
.ws-notification--unread { background:#f5f3ff; }
.ws-notification strong { font-size:.78rem; }
.ws-notification span,.ws-notifications p { font-size:.72rem; color:#6b7280; }
.ws-topbar__notif {
  position: relative;
  background: none;
  border: none;
  color: #9690a8;
  cursor: pointer;
  padding: 0.35rem;
}
.ws-topbar__notif-dot {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 7px;
  height: 7px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid #fff;
}
.ws-topbar__avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c3aed, #c084fc);
  color: #fff;
  font-weight: 800;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.ws-topbar__avatar img {
  width: 100%;
  height: 100%;
  border-radius: inherit;
  object-fit: cover;
}

/* ═══════ SUB NAV ═══════ */
.ws-subnav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}
.ws-subnav__tabs {
  display: flex;
  gap: 0;
}
.ws-subnav__tab {
  padding: 0.85rem 1.25rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #9ca3af;
  border: none;
  background: none;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}
.ws-subnav__tab:hover {
  color: #111;
}
.ws-subnav__tab--active {
  color: #111;
  font-weight: 700;
  border-bottom-color: #111;
}
.ws-subnav__site {
  font-size: 0.75rem;
  font-weight: 600;
  color: #9ca3af;
  text-decoration: none;
  transition: color 0.2s;
}
.ws-subnav__site:hover {
  color: #111;
}

/* ═══════ CONTENT ═══════ */
.ws-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 2rem 2rem;
}
.ws-content::-webkit-scrollbar { width: 6px; }
.ws-content::-webkit-scrollbar-track { background: transparent; }
.ws-content::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }

@media (max-width: 768px) {
  .ws-side { width: 56px; }
  .ws-topbar { padding: 1rem; }
  .ws-topbar__center { display: none; }
  .ws-subnav { padding: 0 1rem; overflow-x: auto; }
  .ws-content { padding: 1rem; }
}
</style>
