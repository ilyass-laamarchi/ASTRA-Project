<!-- Staff category list and editor used by the fleet management workspace. -->
<script setup>
import { computed, onMounted, ref } from 'vue'
import { FolderPlus, Pencil, Search } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const categories = ref([]), search = ref(''), error = ref(''), saving = ref(false)
const editingId = ref(null)
const form = ref({ name: '', description: '', is_active: true })
const prefix = computed(() => `/${auth.user.role}`)
const filtered = computed(() => categories.value.filter(c => `${c.name} ${c.description || ''}`.toLowerCase().includes(search.value.toLowerCase())))

// Load categories available to the signed-in staff role.
async function load() {
  const result = await api.get(`${prefix.value}/categories`)
  categories.value = Array.isArray(result.data) ? result.data : (result.data?.data || [])
}

// Fill the editor with an existing category.
function edit(category) {
  editingId.value = category.id
  form.value = {
    name: category.name,
    description: category.description || '',
    is_active: category.is_active,
  }
}

// Return the editor to its create state.
function reset() {
  editingId.value = null
  form.value = { name: '', description: '', is_active: true }
  error.value = ''
}

// Create or update a category, then refresh the authoritative list.
async function save() {
  saving.value = true
  error.value = ''
  try {
    const url = `${prefix.value}/categories${editingId.value ? `/${editingId.value}` : ''}`
    if (editingId.value) await api.put(url, form.value)
    else await api.post(url, form.value)
    reset()
    await load()
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Enregistrement impossible.'
  } finally {
    saving.value = false
  }
}

// Toggle public visibility without deleting historical category data.
async function toggle(category) {
  const result = await api.patch(`${prefix.value}/categories/${category.id}/activation`)
  Object.assign(category, result.data)
}
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div><h2 class="text-2xl font-black">Catégories</h2><p class="text-sm font-medium text-gray-500">Organisez la flotte publique sans supprimer l'historique.</p></div>
    <form @submit.prevent="save" class="bg-white border border-gray-100 rounded-3xl p-6 grid grid-cols-1 md:grid-cols-4 gap-3">
      <input v-model="form.name" required placeholder="Nom de la catégorie" class="border rounded-xl px-4 py-3 text-sm font-bold">
      <input v-model="form.description" placeholder="Description" class="border rounded-xl px-4 py-3 text-sm md:col-span-2">
      <button :disabled="saving" class="bg-gray-900 text-white rounded-xl font-bold flex items-center justify-center gap-2"><FolderPlus :size="16"/>{{editingId ? 'Mettre à jour' : 'Ajouter'}}</button>
      <p v-if="error" class="text-xs font-bold text-red-600 md:col-span-4">{{error}}</p>
    </form>
    <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden">
      <div class="p-4 border-b relative"><Search :size="16" class="absolute left-7 top-7 text-gray-400"/><input v-model="search" placeholder="Rechercher une catégorie" class="border rounded-xl pl-10 pr-4 py-2 text-sm"></div>
      <table class="w-full text-sm"><thead><tr class="text-left text-gray-400 text-xs uppercase"><th class="p-4">Catégorie</th><th>Description</th><th>État</th><th>Actions</th></tr></thead>
        <tbody><tr v-for="category in filtered" :key="category.id" class="border-t"><td class="p-4 font-black">{{category.name}}</td><td>{{category.description}}</td><td><button @click="toggle(category)" class="font-bold" :class="category.is_active ? 'text-emerald-600' : 'text-red-600'">{{category.is_active ? 'Active' : 'Masquée'}}</button></td><td><button @click="edit(category)" class="p-2" :aria-label="`Modifier ${category.name}`"><Pencil :size="16"/></button></td></tr></tbody>
      </table>
    </div>
  </div>
</template>
