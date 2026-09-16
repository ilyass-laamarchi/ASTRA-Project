<!-- Staff client directory with search, details, and administrator activation controls. -->
<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Search, Mail, Phone, ChevronLeft, ChevronRight, RefreshCw } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const clients = ref([])
const loading = ref(true)
const search = ref('')
const selected = ref(null)
const detailLoading = ref(false)
const error = ref('')
const pagination = ref({ currentPage: 1, lastPage: 1, total: 0 })
const newClientsAvailable = ref(false)
let searchTimer
let refreshTimer

// Load the client directory allowed for the current staff role.
async function loadData(page = 1, { silent = false } = {}) {
  if (!silent) loading.value = true
  error.value = ''
  try {
    const params = { page }
    if (search.value.trim()) params.search = search.value.trim()
    const result = await api.get(`/${auth.user.role}/clients`, { params })
    const pageData = result.data || {}
    const previousTotal = pagination.value.total
    clients.value = Array.isArray(pageData.data) ? pageData.data : []
    pagination.value = {
      currentPage: Number(pageData.current_page || 1),
      lastPage: Number(pageData.last_page || 1),
      total: Number(pageData.total || clients.value.length),
    }
    newClientsAvailable.value = newClientsAvailable.value || (page > 1 && pagination.value.total > previousTotal)
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Chargement des clients impossible.'
  } finally {
    if (!silent) loading.value = false
  }
}

watch(search, () => {
  clearTimeout(searchTimer)
  newClientsAvailable.value = false
  searchTimer = setTimeout(() => loadData(1), 300)
})

/** Keeps the directory synchronized while the admin leaves it open. */
function synchronize() {
  if (document.visibilityState === 'visible') loadData(pagination.value.currentPage, { silent: true })
}

function goToPage(page) {
  if (page >= 1 && page <= pagination.value.lastPage && page !== pagination.value.currentPage) loadData(page)
}

function showNewestClients() {
  newClientsAvailable.value = false
  loadData(1)
}

onMounted(() => {
  loadData()
  window.addEventListener('focus', synchronize)
  document.addEventListener('visibilitychange', synchronize)
  refreshTimer = window.setInterval(synchronize, 15000)
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  clearInterval(refreshTimer)
  window.removeEventListener('focus', synchronize)
  document.removeEventListener('visibilitychange', synchronize)
})

// Activate or suspend a client account; administrators only in the backend.
async function toggleActive(client) {
  try {
    const result = await api.patch(`/admin/clients/${client.id}/activation`)
    Object.assign(client, result.data)
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Modification du statut impossible.'
  }
}

// Fetch the selected client's full staff-visible record.
async function showDetails(client) {
  detailLoading.value = true
  error.value = ''
  try {
    const result = await api.get(`/${auth.user.role}/clients/${client.id}`)
    selected.value = result.data
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Chargement du client impossible.'
  } finally {
    detailLoading.value = false
  }
}
</script>

<template>
  <div class="h-full flex flex-col">
    
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Clients</h2>
        <p class="text-sm font-medium text-gray-500">{{ pagination.total }} comptes · synchronisation automatique</p>
      </div>
      <button type="button" @click="loadData(1)" class="border border-gray-200 bg-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50 transition-colors">
        <RefreshCw :size="15" /> Actualiser
      </button>
    </div>

    <button v-if="newClientsAvailable" type="button" @click="showNewestClients" class="mb-4 rounded-xl bg-blue-50 px-4 py-3 text-left text-sm font-bold text-blue-700">
      De nouveaux clients sont disponibles. Afficher les inscriptions récentes.
    </button>
    <p v-if="error" class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ error }}</p>

    <div class="flex-1 bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col">
      
      <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div class="relative max-w-sm w-full">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input v-model="search" type="text" placeholder="Rechercher (Nom, Email...)" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900">
        </div>
      </div>

      <div class="flex-1 overflow-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead class="bg-white sticky top-0 z-10 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Client</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Contact</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Date d'inscription</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">Chargement...</td>
            </tr>
            <tr v-else-if="clients.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">Aucun client trouvé.</td>
            </tr>
            <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    {{client.first_name?.[0]}}{{client.last_name?.[0]}}
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">{{client.first_name}} {{client.last_name}}</p>
                    <p class="text-xs font-semibold text-gray-500">ID: {{client.id}}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                  <span class="flex items-center gap-2 text-gray-600 font-medium"><Mail :size="14" class="text-gray-400"/> {{client.email}}</span>
                  <span v-if="client.phone" class="flex items-center gap-2 text-gray-600 font-medium"><Phone :size="14" class="text-gray-400"/> {{client.phone}}</span>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="text-gray-900 font-bold">{{new Date(client.created_at).toLocaleDateString('fr-FR')}}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3"><button @click="showDetails(client)" class="text-xs font-black underline">Détails</button><button v-if="auth.user.role === 'admin'" @click="toggleActive(client)" :aria-label="`${client.is_active ? 'Désactiver' : 'Activer'} ${client.full_name}`" :class="[
                  'w-10 h-5 rounded-full relative transition-colors flex items-center',
                  client.is_active ? 'bg-emerald-500' : 'bg-red-500'
                ]">
                  <div :class="['w-4 h-4 bg-white rounded-full shadow-sm absolute transition-transform', client.is_active ? 'translate-x-5' : 'translate-x-1']"></div>
                </button>
                <span v-else class="text-xs font-bold" :class="client.is_active ? 'text-emerald-600' : 'text-red-600'">{{ client.is_active ? 'Actif' : 'Inactif' }}</span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.lastPage > 1" class="border-t border-gray-100 px-5 py-3 flex items-center justify-between bg-gray-50/50">
        <p class="text-xs font-bold text-gray-500">Page {{ pagination.currentPage }} sur {{ pagination.lastPage }} · {{ pagination.total }} clients</p>
        <div class="flex items-center gap-2">
          <button type="button" :disabled="pagination.currentPage === 1" @click="goToPage(pagination.currentPage - 1)" class="w-9 h-9 grid place-items-center rounded-lg border border-gray-200 bg-white disabled:opacity-40" aria-label="Page précédente"><ChevronLeft :size="16" /></button>
          <button type="button" :disabled="pagination.currentPage === pagination.lastPage" @click="goToPage(pagination.currentPage + 1)" class="w-9 h-9 grid place-items-center rounded-lg border border-gray-200 bg-white disabled:opacity-40" aria-label="Page suivante"><ChevronRight :size="16" /></button>
        </div>
      </div>
    </div>
    <div v-if="selected || detailLoading" class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4" @click.self="selected=null">
      <div class="bg-white rounded-3xl p-6 max-w-3xl w-full max-h-[85vh] overflow-auto">
        <p v-if="detailLoading" class="font-bold">Chargement...</p>
        <template v-else><div class="flex justify-between gap-4"><div><h3 class="text-xl font-black">{{selected.full_name}}</h3><p class="text-sm text-gray-500">{{selected.email}} · {{selected.phone||'Téléphone non renseigné'}}</p></div><button @click="selected=null" class="font-black" aria-label="Fermer">×</button></div>
        <h4 class="font-black mt-6 mb-2">Réservations ({{selected.reservations_count}})</h4><div v-if="selected.reservations?.length" class="space-y-2"><div v-for="r in selected.reservations" :key="r.id" class="border rounded-xl p-3 text-sm flex justify-between"><span>#{{r.id}} · {{r.car?.brand}} {{r.car?.model}}</span><strong>{{r.status}}</strong></div></div><p v-else class="text-sm text-gray-500">Aucune réservation.</p>
        <h4 class="font-black mt-6 mb-2">Paiements</h4><div v-if="selected.payments?.length" class="space-y-2"><div v-for="p in selected.payments" :key="p.id" class="border rounded-xl p-3 text-sm flex justify-between"><span>{{p.payment_reference}} · {{p.amount}} {{p.currency}}</span><strong>{{p.status}}</strong></div></div><p v-else class="text-sm text-gray-500">Aucun paiement.</p></template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
