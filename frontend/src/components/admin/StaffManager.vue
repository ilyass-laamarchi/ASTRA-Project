<!-- Administrator-only Responsable account list, editor, and activation controls. -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { Search, UserPlus, Shield } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const staff = ref([])
const loading = ref(true)
const search = ref('')
const showForm = ref(false)
const editingId = ref(null)
const error = ref('')
// Build a fresh form object so previous values cannot leak between editors.
const emptyForm = () => ({first_name:'',last_name:'',email:'',phone:'',password:'',password_confirmation:'',is_active:true})
const form = ref(emptyForm())

// Fetch Responsable accounts managed by administrators.
async function loadData() {
  loading.value = true
  error.value = ''
  try {
    const result = await api.get('/admin/staff')
    staff.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
  } catch(requestError) {
    error.value = requestError.response?.data?.message || 'Chargement des responsables impossible.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const filtered = computed(() => {
  if (!search.value) return staff.value
  const s = search.value.toLowerCase()
  return staff.value.filter(u => 
    u.first_name.toLowerCase().includes(s) || 
    u.last_name.toLowerCase().includes(s) ||
    u.email.toLowerCase().includes(s)
  )
})

// Activate or suspend a staff account through the protected API.
async function toggleActive(user) {
  try {
    const result = await api.patch(`/admin/staff/${user.id}/activation`)
    Object.assign(user, result.data)
  } catch(requestError) {
    error.value = requestError.response?.data?.message || 'Modification du statut impossible.'
  }
}

// Open an empty Responsable creation form.
function openCreate() { editingId.value=null; form.value=emptyForm(); error.value=''; showForm.value=true }

// Open the form with editable values from the selected account.
function openEdit(user) { editingId.value=user.id; form.value={first_name:user.first_name,last_name:user.last_name,email:user.email,phone:user.phone||'',password:'',password_confirmation:'',is_active:user.is_active}; error.value=''; showForm.value=true }

// Close and clear the staff editor.
function closeForm() { showForm.value=false; editingId.value=null; form.value=emptyForm(); error.value='' }

// Create or update a Responsable; the server enforces the staff role.
async function saveStaff() {
  error.value = ''
  try {
    if (editingId.value) await api.put(`/admin/staff/${editingId.value}`, form.value)
    else await api.post('/admin/staff', form.value)
    closeForm(); await loadData()
  } catch(e) { error.value=e.response?.data?.message || 'Enregistrement impossible.' }
}
</script>

<template>
  <div class="h-full flex flex-col">
    
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Équipe Interne</h2>
        <p class="text-sm font-medium text-gray-500">Gérez les comptes des responsables d'agence.</p>
      </div>
      <button @click="openCreate" class="bg-gray-900 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-black transition-colors">
        <UserPlus :size="16" /> Nouveau Responsable
      </button>
    </div>
    <form v-if="showForm" @submit.prevent="saveStaff" class="mb-5 grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 border border-gray-200 rounded-2xl p-4">
      <h3 class="md:col-span-3 font-black">{{ editingId ? 'Modifier le Responsable' : 'Nouveau Responsable' }}</h3>
      <input v-model="form.first_name" required placeholder="Prénom" class="border rounded-lg px-3 py-2 text-sm"><input v-model="form.last_name" required placeholder="Nom" class="border rounded-lg px-3 py-2 text-sm">
      <input v-model="form.email" required type="email" placeholder="E-mail" class="border rounded-lg px-3 py-2 text-sm"><input v-model="form.phone" placeholder="Téléphone" class="border rounded-lg px-3 py-2 text-sm">
      <input v-model="form.password" :required="!editingId" minlength="10" type="password" :placeholder="editingId ? 'Nouveau mot de passe (facultatif)' : 'Mot de passe (10 caractères min.)'" class="border rounded-lg px-3 py-2 text-sm">
      <input v-model="form.password_confirmation" :required="Boolean(form.password)" type="password" placeholder="Confirmer le mot de passe" class="border rounded-lg px-3 py-2 text-sm">
      <p class="text-xs font-bold text-purple-700 md:col-span-3">Rôle imposé par le serveur : Responsable ASTRA.</p>
      <p v-if="error" class="text-xs font-bold text-red-600 md:col-span-2">{{error}}</p><div class="flex gap-2"><button type="button" @click="closeForm" class="border rounded-lg px-4 py-2 text-sm font-bold">Annuler</button><button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Enregistrer</button></div>
    </form>

    <div class="flex-1 bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col">
      
      <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div class="relative max-w-sm w-full">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input v-model="search" type="text" placeholder="Rechercher..." class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900">
        </div>
      </div>

      <div class="flex-1 overflow-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead class="bg-white sticky top-0 z-10 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Responsable</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Email</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Statut</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">Chargement...</td>
            </tr>
            <tr v-else-if="filtered.length === 0">
              <td colspan="4" class="px-6 py-8 text-center text-gray-400 font-bold">Aucun responsable trouvé.</td>
            </tr>
            <tr v-for="user in filtered" :key="user.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center">
                    <Shield :size="18" />
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">{{user.first_name}} {{user.last_name}}</p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-purple-600">{{user.role === 'admin' ? 'Administrateur' : 'Responsable'}}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-bold text-gray-600">{{user.email}}</td>
              <td class="px-6 py-4">
                <span :class="['px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest', user.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600']">{{user.is_active ? 'Actif' : 'Inactif'}}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-3"><button @click="openEdit(user)" class="text-xs font-black underline">Modifier</button><button v-if="user.id !== auth.user?.id" @click="toggleActive(user)" :aria-label="user.is_active ? 'Désactiver le compte' : 'Activer le compte'" :class="[
                  'w-10 h-5 rounded-full relative transition-colors flex items-center',
                  user.is_active ? 'bg-emerald-500' : 'bg-red-500'
                ]">
                  <div :class="['w-4 h-4 bg-white rounded-full shadow-sm absolute transition-transform', user.is_active ? 'translate-x-5' : 'translate-x-1']"></div>
                </button><span v-else class="text-xs font-bold text-gray-500">Votre compte</span></div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
