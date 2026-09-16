<!-- Staff payment list and administrator refund confirmation interface. -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { Search, CreditCard, DollarSign } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()

const payments = ref([])
const loading = ref(true)
const search = ref('')
const refundTarget = ref(null)
const actionMessage = ref('')
const actionError = ref('')

// Load personal payments for clients or the staff-wide payment register.
async function loadData() {
  loading.value = true
  try {
    const result = await api.get(auth.user.role === 'client' ? '/my-payments' : `/${auth.user.role}/payments`)
    payments.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
  } catch (requestError) {
    actionError.value = requestError.response?.data?.message || 'Chargement des paiements impossible.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const filtered = computed(() => {
  if (!search.value) return payments.value
  const s = search.value.toLowerCase()
  return payments.value.filter(p => 
    (p.provider_payment_id || p.payment_reference || '').toLowerCase().includes(s) || 
    String(p.reservation_id).includes(s)
  )
})

// Format monetary values consistently in Moroccan dirhams.
const formatCurrency = (value) => Number(value).toLocaleString('fr-MA', { style: 'currency', currency: 'MAD' })
const statusLabel = { pending:'En attente', processing:'En traitement', paid:'Payé', failed:'Échoué', cancelled:'Annulé', refunded:'Remboursé' }

// Ask the backend to refund the selected paid transaction.
async function confirmRefund() {
  actionMessage.value=''; actionError.value=''
  try { const result = await api.post(`/admin/payments/${refundTarget.value.id}/refund`); Object.assign(refundTarget.value, result.data); actionMessage.value='Remboursement confirmé.'; refundTarget.value=null }
  catch (error) { actionError.value=error.response?.data?.message || 'Remboursement impossible.' }
}
</script>

<template>
  <div class="h-full flex flex-col">
    
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Paiements</h2>
        <p class="text-sm font-medium text-gray-500">Consultez l'historique des transactions Stripe.</p>
      </div>
    </div>
    <p v-if="actionMessage" class="mb-4 p-3 bg-emerald-50 text-emerald-700 rounded-xl text-sm font-bold">{{actionMessage}}</p><p v-if="actionError" class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm font-bold">{{actionError}}</p>

    <div class="flex-1 bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col">
      
      <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div class="relative max-w-sm w-full">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input v-model="search" type="text" placeholder="Rechercher (ID Stripe, Réservation...)" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900">
        </div>
      </div>

      <div class="flex-1 overflow-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead class="bg-white sticky top-0 z-10 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Transaction</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Réservation</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Montant</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Statut</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">Chargement...</td>
            </tr>
            <tr v-else-if="filtered.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-bold">Aucun paiement trouvé.</td>
            </tr>
            <tr v-for="payment in filtered" :key="payment.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-900">
                    <CreditCard :size="16" />
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">Stripe</p>
                    <p class="text-[10px] font-mono font-bold text-gray-500">{{payment.provider_payment_id || payment.payment_reference}}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-mono text-xs font-bold text-gray-900">#{{payment.reservation_id}}</td>
              <td class="px-6 py-4 font-black text-gray-900">{{formatCurrency(payment.amount)}}</td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest',
                  payment.status === 'paid' ? 'bg-emerald-50 text-emerald-600' : payment.status === 'refunded' ? 'bg-purple-50 text-purple-600' : 'bg-amber-50 text-amber-700'
                ]">
                  {{ statusLabel[payment.status] || payment.status }}
                </span>
                <button v-if="auth.user.role === 'admin' && payment.status === 'paid'" @click="refundTarget=payment;actionError=''" class="ml-2 text-xs font-bold underline">Rembourser</button>
              </td>
              <td class="px-6 py-4 text-gray-900 font-bold">
                {{new Date(payment.created_at).toLocaleString('fr-FR')}}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-if="refundTarget" class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4" @click.self="refundTarget=null"><div class="bg-white rounded-3xl p-6 max-w-md w-full space-y-4"><h3 class="text-lg font-black">Confirmer le remboursement</h3><p class="text-sm text-gray-600">Rembourser {{refundTarget.payment_reference}} pour {{formatCurrency(refundTarget.amount)}} ? Cette demande sera envoyée au prestataire de paiement.</p><p v-if="actionError" class="text-sm font-bold text-red-600">{{actionError}}</p><div class="flex justify-end gap-2"><button @click="refundTarget=null" class="border rounded-xl px-4 py-2 font-bold">Annuler</button><button @click="confirmRefund" class="bg-gray-900 text-white rounded-xl px-4 py-2 font-bold">Confirmer</button></div></div></div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
