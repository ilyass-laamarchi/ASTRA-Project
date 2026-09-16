<!-- Staff fleet manager for car records, activation, operational state, and ordered images. -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import { Search, Plus, Edit2, Trash2, Image as ImageIcon, CheckCircle, XCircle, ArrowLeft, UploadCloud, GripVertical, Star, Car, Settings, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import api from '../../lib/api'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const cars = ref([])
const categories = ref([])
const loading = ref(true)

// Modes: 'list', 'create', 'edit'
const mode = ref('list')
const search = ref('')

const defaultForm = {
  brand: '', model: '', category_id: null, year: new Date().getFullYear(),
  color: '', registration_number: '', seats: 5, doors: 5,
  fuel_type: 'gasoline', transmission: 'automatic',
  daily_price: 0, mileage: 0, description: '',
  operational_status: 'available', is_active: true
}
const form = ref({ ...defaultForm })
const currentImages = ref([]) // From server
const newImages = ref([]) // Files to upload

const formError = ref(null)
const formLoading = ref(false)
const prefix = computed(() => `/${auth.user.role}`)

// Load the fleet and category choices in one request cycle.
async function loadData() {
  loading.value = true
  try {
    const [carRes, catRes] = await Promise.all([
      api.get(`${prefix.value}/cars`),
      api.get(`${prefix.value}/categories`)
    ])
    cars.value = Array.isArray(carRes.data) ? carRes.data : (carRes.data?.data || [])
    categories.value = Array.isArray(catRes.data) ? catRes.data : (catRes.data?.data || [])
  } catch (requestError) {
    formError.value = requestError.response?.data?.message || 'Chargement de la flotte impossible.'
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const filteredCars = computed(() => {
  if (!search.value) return cars.value
  const s = search.value.toLowerCase()
  return cars.value.filter(c => 
    c.brand.toLowerCase().includes(s) || 
    c.model.toLowerCase().includes(s) || 
    c.registration_number.toLowerCase().includes(s)
  )
})

// Prepare the editor for a new vehicle.
function openCreate() {
  form.value = { ...defaultForm, category_id: categories.value[0]?.id }
  currentImages.value = []
  newImages.value = []
  formError.value = null
  mode.value = 'create'
}

// Prepare the editor with an existing vehicle and its images.
function openEdit(car) {
  form.value = { ...car }
  currentImages.value = [...(car.images || [])]
  newImages.value = []
  formError.value = null
  mode.value = 'edit'
}

// Return to the fleet list without mutating server data.
function goBack() {
  mode.value = 'list'
}

// Add selected files as local previews until the vehicle is saved.
function onFileSelected(event) {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    newImages.value.push({
      file,
      preview: URL.createObjectURL(file),
      isPrimary: currentImages.value.length === 0 && newImages.value.length === 0
    })
  })
}

// Remove a not-yet-uploaded preview and release its object URL.
function removeNewImage(index) {
  URL.revokeObjectURL(newImages.value[index].preview)
  newImages.value.splice(index, 1)
}

// Delete a persisted image through the role-protected API.
async function removeCurrentImage(id) {
  try {
    await api.delete(`${prefix.value}/cars/${form.value.id}/images/${id}`)
    currentImages.value = currentImages.value.filter(img => img.id !== id)
  } catch (requestError) {
    formError.value = requestError.response?.data?.message || 'Suppression de l’image impossible.'
  }
}

// Promote one persisted image to the public primary image.
async function setPrimaryImage(image) {
  await api.patch(`${prefix.value}/cars/${form.value.id}/images/${image.id}/primary`)
  currentImages.value.forEach(item => { item.is_primary = item.id === image.id })
}

// Reorder persisted images and save the full ordering atomically.
async function moveImage(index, offset) {
  const target=index+offset
  if(target<0||target>=currentImages.value.length)return
  const reordered=[...currentImages.value]
  ;[reordered[index],reordered[target]]=[reordered[target],reordered[index]]
  currentImages.value=reordered
  await api.patch(`${prefix.value}/cars/${form.value.id}/images/reorder`,{image_ids:reordered.map(image=>image.id)})
}

// Save vehicle fields first, then attach any newly selected images.
async function saveCar() {
  formLoading.value = true
  formError.value = null
  try {
    let savedCar
    if (mode.value === 'create') {
      const { data } = await api.post(`${prefix.value}/cars`, form.value)
      savedCar = data
    } else {
      const { data } = await api.put(`${prefix.value}/cars/${form.value.id}`, form.value)
      savedCar = data
    }
    
    // New files require the saved vehicle identifier.
    if (newImages.value.length) {
      const formData = new FormData()
      newImages.value.forEach(img => formData.append('images[]', img.file))
      await api.post(`${prefix.value}/cars/${savedCar.id}/images`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
    }
    
    await loadData()
    mode.value = 'list'
  } catch (requestError) {
    formError.value = requestError.response?.data?.message || 'Erreur lors de la sauvegarde.'
  } finally {
    formLoading.value = false
  }
}

// Activate or archive a car without deleting reservation history.
async function toggleActive(car) {
  try {
    const result = await api.patch(`${prefix.value}/cars/${car.id}/activation`)
    Object.assign(car, result.data)
  } catch (requestError) {
    formError.value = requestError.response?.data?.message || 'Modification du statut impossible.'
  }
}
</script>

<template>
  <div class="h-full flex flex-col">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Gestion des Véhicules</h2>
        <p class="text-sm font-medium text-gray-500">Ajoutez, modifiez ou retirez des véhicules de la flotte.</p>
      </div>
      <button v-if="mode === 'list'" @click="openCreate" class="bg-gray-900 text-white px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-black transition-colors">
        <Plus :size="16" /> Ajouter un véhicule
      </button>
      <button v-else @click="goBack" class="bg-white border border-gray-200 text-gray-900 px-4 py-2 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50 transition-colors">
        <ArrowLeft :size="16" /> Retour à la liste
      </button>
    </div>

    <!-- List Mode -->
    <div v-if="mode === 'list'" class="flex-1 bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] overflow-hidden flex flex-col">
      
      <!-- Toolbar -->
      <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <div class="relative max-w-sm w-full">
          <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input v-model="search" type="text" placeholder="Rechercher (Marque, Modèle, Immat...)" class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-gray-900">
        </div>
      </div>

      <!-- Table -->
      <div class="flex-1 overflow-auto custom-scrollbar">
        <table class="w-full text-left text-sm">
          <thead class="bg-white sticky top-0 z-10 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Véhicule</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Immatriculation</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Tarif/j</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Statut Opérationnel</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Visibilité</th>
              <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">Chargement...</td>
            </tr>
            <tr v-else-if="filteredCars.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">Aucun véhicule trouvé.</td>
            </tr>
            <tr v-for="car in filteredCars" :key="car.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-gray-100 overflow-hidden border border-gray-200 flex-shrink-0">
                    <img v-if="car.images?.[0]" :src="car.images[0].path" class="w-full h-full object-cover" />
                    <Car v-else :size="20" class="text-gray-400 m-auto mt-2" />
                  </div>
                  <div>
                    <p class="font-bold text-gray-900">{{car.brand}} {{car.model}}</p>
                    <p class="text-xs font-semibold text-gray-500">{{car.category?.name}} • {{car.year}}</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-mono text-xs font-bold text-gray-700">{{car.registration_number}}</td>
              <td class="px-6 py-4 font-bold text-gray-900">{{Number(car.daily_price).toLocaleString('fr-MA')}} <span class="text-xs text-gray-500 font-normal">MAD</span></td>
              <td class="px-6 py-4">
                <span :class="[
                  'px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5',
                  car.operational_status === 'available' ? 'bg-emerald-50 text-emerald-600' :
                  car.operational_status === 'maintenance' ? 'bg-orange-50 text-orange-600' : 'bg-red-50 text-red-600'
                ]">
                  <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                  {{ car.operational_status === 'available' ? 'Disponible' : car.operational_status === 'maintenance' ? 'Maintenance' : 'Réparation' }}
                </span>
              </td>
              <td class="px-6 py-4">
                <button @click="toggleActive(car)" :class="[
                  'w-10 h-5 rounded-full relative transition-colors flex items-center',
                  car.is_active ? 'bg-gray-900' : 'bg-gray-200'
                ]">
                  <div :class="['w-4 h-4 bg-white rounded-full shadow-sm absolute transition-transform', car.is_active ? 'translate-x-5' : 'translate-x-1']"></div>
                </button>
              </td>
              <td class="px-6 py-4 text-right">
                <button @click="openEdit(car)" class="p-2 text-gray-400 hover:text-gray-900 transition-colors">
                  <Edit2 :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create/Edit Form Mode -->
    <div v-else class="flex-1 overflow-y-auto custom-scrollbar">
      <form @submit.prevent="saveCar" class="max-w-5xl space-y-6 pb-20">
        
        <div class="bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] p-6 md:p-8">
          <h3 class="text-lg font-black text-gray-900 mb-6 flex items-center gap-2">
            <Car :size="20"/> Informations Principales
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Marque</label>
              <input v-model="form.brand" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Modèle</label>
              <input v-model="form.model" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Catégorie</label>
              <select v-model="form.category_id" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
                <option v-for="c in categories" :key="c.id" :value="c.id">{{c.name}}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Année</label>
              <input v-model="form.year" type="number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Couleur</label>
              <input v-model="form.color" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Immatriculation</label>
              <input v-model="form.registration_number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all font-mono">
            </div>
          </div>
        </div>

        <div class="bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] p-6 md:p-8">
          <h3 class="text-lg font-black text-gray-900 mb-6 flex items-center gap-2">
            <Settings :size="20"/> Spécifications & Tarification
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Places</label>
              <input v-model="form.seats" type="number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Portes</label>
              <input v-model="form.doors" type="number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Carburant</label>
              <select v-model="form.fuel_type" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
                <option value="gasoline">Essence</option>
                <option value="diesel">Diesel</option>
                <option value="hybrid">Hybride</option>
                <option value="electric">Électrique</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Transmission</label>
              <select v-model="form.transmission" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
                <option value="manual">Manuelle</option>
                <option value="automatic">Automatique</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Kilométrage</label>
              <input v-model="form.mileage" type="number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Prix par Jour (MAD)</label>
              <input v-model="form.daily_price" type="number" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-black focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all text-blue-600">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Statut Opérationnel</label>
              <select v-model="form.operational_status" required class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all">
                <option value="available">Disponible</option>
                <option value="maintenance">En Maintenance</option>
                <option value="unavailable">Indisponible</option>
              </select>
            </div>
            <div class="flex items-center pt-8">
              <label class="flex items-center cursor-pointer">
                <div class="relative">
                  <input v-model="form.is_active" type="checkbox" class="sr-only">
                  <div class="block bg-gray-200 w-10 h-6 rounded-full transition-colors" :class="{'bg-gray-900': form.is_active}"></div>
                  <div class="dot absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform" :class="{'translate-x-4': form.is_active}"></div>
                </div>
                <span class="ml-3 text-xs font-bold text-gray-700">Visible Publiquement</span>
              </label>
            </div>
          </div>
          
          <div class="mt-6">
            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Description</label>
            <textarea v-model="form.description" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:bg-white focus:border-gray-900 focus:ring-1 focus:ring-gray-900 transition-all"></textarea>
          </div>
        </div>

        <!-- Images Section -->
        <div class="bg-white rounded-[1.5rem] border border-gray-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)] p-6 md:p-8">
          <h3 class="text-lg font-black text-gray-900 mb-6 flex items-center gap-2">
            <ImageIcon :size="20"/> Gestion des Images
          </h3>
          
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
            <!-- Existing Images -->
            <div v-for="img in currentImages" :key="img.id" class="relative group rounded-xl overflow-hidden border-2 border-gray-100 aspect-video">
              <img :src="img.path" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button type="button" @click="setPrimaryImage(img)" class="w-8 h-8 rounded-full bg-yellow-400 text-gray-900 flex items-center justify-center" aria-label="Définir comme image principale"><Star :size="14" /></button>
                <button type="button" @click="moveImage(currentImages.indexOf(img),-1)" :disabled="currentImages.indexOf(img)===0" class="w-8 h-8 rounded-full bg-white text-gray-900 flex items-center justify-center disabled:opacity-30" aria-label="Déplacer à gauche"><ChevronLeft :size="14" /></button>
                <button type="button" @click="moveImage(currentImages.indexOf(img),1)" :disabled="currentImages.indexOf(img)===currentImages.length-1" class="w-8 h-8 rounded-full bg-white text-gray-900 flex items-center justify-center disabled:opacity-30" aria-label="Déplacer à droite"><ChevronRight :size="14" /></button>
                <button type="button" @click="removeCurrentImage(img.id)" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors">
                  <Trash2 :size="14" />
                </button>
              </div>
              <div v-if="img.is_primary" class="absolute top-2 left-2 bg-yellow-400 text-yellow-900 text-[10px] font-black uppercase px-2 py-0.5 rounded-full flex items-center gap-1">
                <Star :size="10" /> Principal
              </div>
            </div>
            
            <!-- New Images -->
            <div v-for="(img, idx) in newImages" :key="idx" class="relative group rounded-xl overflow-hidden border-2 border-blue-200 aspect-video">
              <img :src="img.preview" class="w-full h-full object-cover opacity-80" />
              <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <button type="button" @click="removeNewImage(idx)" class="w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors">
                  <Trash2 :size="14" />
                </button>
              </div>
              <div class="absolute top-2 left-2 bg-blue-500 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded-full">
                Nouveau
              </div>
            </div>

            <!-- Upload Button -->
            <label class="border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center text-gray-400 hover:border-gray-500 hover:text-gray-600 transition-colors cursor-pointer aspect-video bg-gray-50">
              <UploadCloud :size="24" class="mb-2" />
              <span class="text-xs font-bold">Ajouter</span>
              <input type="file" multiple accept="image/*" class="hidden" @change="onFileSelected">
            </label>
          </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-end gap-4">
          <p v-if="formError" class="text-xs font-bold text-red-600">{{formError}}</p>
          <button type="button" @click="goBack" class="px-6 py-3 rounded-xl font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
            Annuler
          </button>
          <button type="submit" :disabled="formLoading" class="px-8 py-3 rounded-xl font-black uppercase tracking-widest text-white bg-gray-900 hover:bg-black transition-colors disabled:opacity-50">
            {{ formLoading ? 'Sauvegarde...' : 'Enregistrer le véhicule' }}
          </button>
        </div>

      </form>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>
