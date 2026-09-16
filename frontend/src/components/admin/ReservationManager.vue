<!-- Staff reservation table and detail workflow for confirm, reject, cancel, and complete actions. -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { Search, Calendar as CalendarIcon, User, Car, Clock, CheckCircle, XCircle, FileText } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const reservations = ref([])
const loading = ref(true)
const search = ref('')
const filterStatus = ref('all') // all, pending, confirmed, active, completed, cancelled
const actionError = ref('')
const paymentAvailable = ref(false)
const rejectionTarget = ref(null)
const rejectionReason = ref('')

// Load role-scoped reservations and client checkout readiness.
async function loadData() {
  loading.value = true
  try {
    const isClient = auth.user?.role === 'client'
    const [result, paymentConfiguration] = await Promise.all([
      api.get(isClient ? '/my-reservations' : `/${auth.user.role}/reservations`),
      isClient ? api.get('/payment-configuration') : Promise.resolve({ data: { checkout_available: false } }),
    ])
    reservations.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
    paymentAvailable.value = Boolean(paymentConfiguration.data?.checkout_available)
  } catch (requestError) {
    actionError.value = requestError.response?.data?.message || 'Chargement des réservations impossible.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const filtered = computed(() => {
  let list = reservations.value
  if (filterStatus.value !== 'all') {
    list = list.filter(r => r.status === filterStatus.value)
  }
  if (search.value) {
    const s = search.value.toLowerCase()
    list = list.filter(r => 
      r.car?.brand.toLowerCase().includes(s) ||
      r.user?.last_name.toLowerCase().includes(s) ||
      r.id.toString().includes(s)
    )
  }
  return list
})

// Execute a server-authorized reservation state transition.
async function updateStatus(id, newStatus, payload = {}) {
  try {
    const isClient = auth.user?.role === 'client'
    if (isClient && newStatus === 'cancelled') {
      await api.patch(`/my-reservations/${id}/cancel`)
    } else {
      const endpointMap = {
        confirmed: 'confirm',
        rejected: 'reject',
        cancelled: 'cancel',
        completed: 'complete'
      }
      await api.patch(`/${auth.user.role}/reservations/${id}/${endpointMap[newStatus]}`, payload)
    }
    const r = reservations.value.find(res => res.id === id)
    if (r) r.status = newStatus
  } catch (requestError) {
    actionError.value = requestError.response?.data?.message || 'Modification de la réservation impossible.'
  }
}

// Reject the pending reservation with the required internal reason.
async function confirmRejection() {
  if (!rejectionReason.value.trim()) return
  await updateStatus(rejectionTarget.value, 'rejected', { internal_note: rejectionReason.value.trim() })
  rejectionTarget.value=null; rejectionReason.value=''
}

// Start server-created Stripe Checkout for an eligible client reservation.
async function checkout(id) {
  actionError.value = ''
  try {
    const result = await api.post(`/reservations/${id}/checkout`)
    const url = result.checkout_url || result.data?.checkout_url
    if (url) window.location.assign(url)
  } catch (error) {
    actionError.value = error.response?.data?.message || 'Le paiement est indisponible.'
  }
}

const statusColor = {
  pending: 'bg-yellow-50 text-yellow-600',
  confirmed: 'bg-blue-50 text-blue-600',
  active: 'bg-emerald-50 text-emerald-600',
  completed: 'bg-gray-100 text-gray-600',
  cancelled: 'bg-gray-50 text-gray-600',
  rejected: 'bg-red-50 text-red-600'
}
const statusLabel = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  active: 'En cours',
  completed: 'Terminée',
  cancelled: 'Annulée',
  rejected: 'Refusée'
}
</script>

<template>
  <div class="h-full flex flex-col">
    
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Réservations</h2>
        <p class="text-sm font-medium text-gray-500">Gérez le cycle de vie des locations.</p>
      </div>
    </div>

    <div class="flex-1 bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col">
      
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 flex flex-wrap gap-4 items-center justify-between bg-gray-50/50">
        <div class="relative max-w-sm w-full">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input v-model="search" type="text" placeholder="Rechercher par client, véhicule ou #ID" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900">
        </div>
        
        <select v-model="filterStatus" class="bg-white border border-gray-200 text-sm font-bold text-gray-700 rounded-lg px-4 py-2 focus:outline-none">
          <option value="all">Tous les statuts</option>
          <option value="pending">En attente</option>
          <option value="confirmed">Confirmée</option>
          <option value="completed">Terminée</option>
          <option value="cancelled">Annulée</option>
        </select>
      </div>
      <p v-if="actionError" class="px-4 py-2 text-xs font-bold text-red-600 bg-red-50">{{ actionError }}</p>

      <!-- Table -->
      <div class="flex-1 overflow-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead class="bg-white sticky top-0 z-10 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">ID</th>
              <th v-if="auth.user?.role !== 'client'" class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Client</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Véhicule</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Période</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Statut</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">Chargement...</td>
            </tr>
            <tr v-else-if="filtered.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">Aucune réservation.</td>
            </tr>
            <tr v-for="res in filtered" :key="res.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4 font-mono text-xs font-bold text-gray-400">#{{res.id}}</td>
              <td v-if="auth.user?.role !== 'client'" class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 font-bold text-xs">
                    {{res.user?.first_name?.[0]}}{{res.user?.last_name?.[0]}}
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">{{res.user?.first_name}} {{res.user?.last_name}}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-bold text-gray-900">{{res.car?.brand}} {{res.car?.model}}</td>
              <td class="px-6 py-4">
                <p class="text-xs font-bold text-gray-900">{{new Date(res.start_date).toLocaleDateString('fr-FR')}}</p>
                <p class="text-xs text-gray-500 font-medium">au {{new Date(res.end_date).toLocaleDateString('fr-FR')}}</p>
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                  <span :class="['w-fit px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest', statusColor[res.status]]">
                    {{statusLabel[res.status]}}
                  </span>
                  <p v-if="res.status === 'rejected' && res.rejection_reason" class="text-xs text-red-500 font-bold max-w-[200px] truncate" :title="res.rejection_reason">
                    {{ res.rejection_reason }}
                  </p>
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <template v-if="auth.user?.role !== 'client'">
                    <!-- State Machine Actions for Admin -->
                    <button v-if="res.status === 'pending'" @click="updateStatus(res.id, 'confirmed')" class="px-2 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-xs font-bold transition-colors">Confirmer</button>
                    <button v-if="res.status === 'pending'" @click="rejectionTarget=res.id;rejectionReason=''" class="px-2 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-xs font-bold transition-colors">Rejeter</button>
                    
                    <button v-if="res.status === 'confirmed'" @click="updateStatus(res.id, 'cancelled')" class="px-2 py-1 bg-red-50 text-red-600 hover:bg-red-100 rounded text-xs font-bold transition-colors">Annuler</button>
                    <button v-if="res.status === 'confirmed'" @click="updateStatus(res.id, 'completed')" class="px-2 py-1 bg-gray-900 text-white hover:bg-black rounded text-xs font-bold transition-colors">Terminer (Retour)</button>
                  </template>
                  <template v-else>
                    <!-- Actions for Client -->
                    <button v-if="res.status === 'pending'" @click="updateStatus(res.id, 'cancelled')" class="px-2 py-1 bg-gray-50 text-gray-600 hover:bg-gray-100 rounded text-xs font-bold transition-colors">Annuler</button>
                    <button v-if="res.status === 'confirmed' && paymentAvailable" @click="checkout(res.id)" class="px-2 py-1 bg-gray-900 text-white rounded text-xs font-bold">Payer maintenant</button>
                    <button v-else-if="res.status === 'confirmed'" type="button" disabled class="px-2 py-1 bg-gray-100 text-gray-400 rounded text-xs font-bold cursor-not-allowed">Paiement temporairement indisponible</button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-if="rejectionTarget" class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4" @click.self="rejectionTarget=null">
      <form @submit.prevent="confirmRejection" class="bg-white rounded-3xl p-6 max-w-lg w-full space-y-4"><h3 class="text-lg font-black">Motif du refus</h3><p class="text-sm text-gray-500">Ce motif sera enregistré et visible par le client.</p><textarea v-model="rejectionReason" required maxlength="2000" rows="5" class="w-full border rounded-xl p-3" placeholder="Expliquez clairement le refus."></textarea><div class="flex justify-end gap-2"><button type="button" @click="rejectionTarget=null" class="px-4 py-2 border rounded-xl font-bold">Annuler</button><button class="px-4 py-2 bg-red-600 text-white rounded-xl font-bold">Confirmer le refus</button></div></form>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
