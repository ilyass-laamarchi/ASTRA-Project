<!-- Complete premium vehicle editor with multi-image drag & drop upload. -->
<script setup>
import { reactive, ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { UploadCloud, X, Save, ArrowLeft, Image as ImageIcon } from 'lucide-vue-next';
import api from '../lib/api';

const props = defineProps({ role: String });
const route = useRoute();
const router = useRouter();
const categories = ref([]);
const images = ref([]); // new files to upload
const imagePreviews = ref([]);
const existingImages = ref([]); // images already on the server
const error = ref('');
const saving = ref(false);
const pendingDeleteId = ref(null);

const form = reactive({
  category_id: '', registration_number: '', brand: '', model: '',
  year: new Date().getFullYear(), color: '', seats: 5, doors: 5,
  fuel_type: 'gasoline', transmission: 'manual', daily_price: '',
  mileage: 0, description: '', operational_status: 'available', is_active: true
});

onMounted(async () => {
  try {
    categories.value = (await api.get(`/${props.role}/categories`)).data.data;
    if (route.params.id) {
      const car = (await api.get(`/${props.role}/cars/${route.params.id}`)).data.data;
      Object.assign(form, car);
      if (car.images) {
        existingImages.value = car.images;
      }
    }
  } catch (e) {
    error.value = "Erreur lors du chargement des données.";
  }
});

/** Adds files dropped onto the vehicle image area. */
function handleFileDrop(e) {
  const files = Array.from(e.dataTransfer?.files || e.target?.files || []).filter(f => f.type.startsWith('image/'));
  addFiles(files);
}

/** Validates selected image files and creates local previews before upload. */
function addFiles(files) {
  files.forEach(file => {
    images.value.push(file);
    const reader = new FileReader();
    reader.onload = e => imagePreviews.value.push({ url: e.target.result, file });
    reader.readAsDataURL(file);
  });
}

/** Removes one not-yet-uploaded image preview. */
function removePreview(index) {
  images.value.splice(index, 1);
  imagePreviews.value.splice(index, 1);
}

/** Deletes one persisted car image through the staff API. */
async function removeExistingImage(imageId) {
  try {
    await api.delete(`/${props.role}/cars/${route.params.id}/images/${imageId}`);
    existingImages.value = existingImages.value.filter(img => img.id !== imageId);
    pendingDeleteId.value = null;
  } catch (e) {
    error.value = "Impossible de supprimer l'image.";
  }
}

/** Saves car fields, uploads queued images, and returns to the fleet list. */
async function save() {
  saving.value = true;
  error.value = '';
  try {
    const { data } = route.params.id 
      ? await api.put(`/${props.role}/cars/${route.params.id}`, form) 
      : await api.post(`/${props.role}/cars`, form);
    
    const car = data.data;
    
    if (images.value.length) {
      const body = new FormData();
      images.value.forEach(file => body.append('images[]', file));
      await api.post(`/${props.role}/cars/${car.id}/images`, body);
    }
    
    router.push(`/${props.role}/cars`);
  } catch (e) {
    error.value = e.response?.data?.message || 'Vérifiez les champs du véhicule.';
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="max-w-5xl">
    <div class="flex items-center justify-between mb-8">
      <div class="flex items-center gap-4">
        <RouterLink :to="`/${role}/cars`" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-500 hover:text-gray-900 shadow-sm transition-all"><ArrowLeft :size="18"/></RouterLink>
        <h2 class="text-2xl font-black text-gray-900">{{route.params.id ? 'Modifier le véhicule' : 'Ajouter un véhicule'}}</h2>
      </div>
      <button @click="save" :disabled="saving" class="bg-[#FFD100] hover:bg-[#E6BC00] text-gray-900 font-bold px-6 py-2.5 rounded-[1rem] shadow-sm transition-all flex items-center gap-2">
        <Save :size="18"/> {{saving ? 'Enregistrement...' : 'Enregistrer'}}
      </button>
    </div>

    <p v-if="error" class="mb-6 font-bold text-xs text-red-600 bg-red-50 px-4 py-3 rounded-xl border border-red-100 flex items-center gap-2">
      <X :size="14" class="text-red-500"/> {{error}}
    </p>

    <form class="grid gap-6 md:grid-cols-3" @submit.prevent="save">
      
      <!-- Left Column: Fields -->
      <div class="md:col-span-2 space-y-6">
        
        <!-- General Info Card -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_10px_30px_rgba(0,0,0,0.02)] border border-gray-100/50">
          <h3 class="text-lg font-black text-gray-900 mb-6">Informations Générales</h3>
          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Marque</label>
              <input v-model="form.brand" required placeholder="Ex: Mercedes-Benz" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Modèle</label>
              <input v-model="form.model" required placeholder="Ex: Classe S" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Catégorie</label>
              <select v-model="form.category_id" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
                <option disabled value="">Sélectionner</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{cat.name}}</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Immatriculation</label>
              <input v-model="form.registration_number" required placeholder="AB-123-CD" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none uppercase">
            </div>
            <div class="sm:col-span-2">
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Description</label>
              <textarea v-model="form.description" rows="3" placeholder="Description du véhicule..." class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none"></textarea>
            </div>
          </div>
        </div>

        <!-- Technical Specs Card -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_10px_30px_rgba(0,0,0,0.02)] border border-gray-100/50">
          <h3 class="text-lg font-black text-gray-900 mb-6">Spécifications Techniques</h3>
          <div class="grid gap-5 sm:grid-cols-3">
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Année</label>
              <input v-model.number="form.year" type="number" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Kilométrage</label>
              <input v-model.number="form.mileage" type="number" min="0" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Couleur</label>
              <input v-model="form.color" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
            </div>
            
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Énergie</label>
              <select v-model="form.fuel_type" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
                <option value="gasoline">Essence</option><option value="diesel">Diesel</option><option value="hybrid">Hybride</option><option value="electric">Électrique</option>
              </select>
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Transmission</label>
              <select v-model="form.transmission" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
                <option value="manual">Manuelle</option><option value="automatic">Automatique</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Places</label>
                <input v-model.number="form.seats" type="number" min="2" max="9" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-center text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
              </div>
              <div>
                <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Portes</label>
                <input v-model.number="form.doors" type="number" min="2" max="6" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-center text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Right Column: Images & Status -->
      <div class="space-y-6">
        
        <!-- Pricing & Status -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_10px_30px_rgba(0,0,0,0.02)] border border-gray-100/50">
          <h3 class="text-lg font-black text-gray-900 mb-6">Tarification</h3>
          
          <div class="mb-6">
            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">Prix / Jour (MAD)</label>
            <div class="relative">
              <input v-model.number="form.daily_price" type="number" min="1" required class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] pl-4 pr-12 py-3.5 text-xl font-black focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
              <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">MAD</span>
            </div>
          </div>

          <div class="h-px bg-gray-100 w-full mb-6"></div>

          <div class="mb-5">
            <label class="block text-[11px] font-bold text-gray-500 uppercase tracking-wider mb-2">État Opérationnel</label>
            <select v-model="form.operational_status" class="w-full bg-gray-50 border border-transparent text-gray-900 rounded-[1rem] px-4 py-3 text-sm font-semibold focus:ring-2 focus:ring-[#FFD100] focus:bg-white transition-all outline-none">
              <option value="available">Disponible</option><option value="maintenance">En maintenance</option><option value="unavailable">Indisponible</option>
            </select>
          </div>

          <label class="flex items-center gap-3 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-5 h-5 text-gray-900 rounded bg-gray-50 border-gray-200 focus:ring-gray-900">
            <span class="text-sm font-bold text-gray-700">Véhicule Actif</span>
          </label>
        </div>

        <!-- Images Upload -->
        <div class="bg-white rounded-[2rem] p-8 shadow-[0_10px_30px_rgba(0,0,0,0.02)] border border-gray-100/50">
          <h3 class="text-lg font-black text-gray-900 mb-6">Photos du Véhicule</h3>
          
          <div class="border-2 border-dashed border-gray-200 rounded-[1.5rem] p-6 text-center hover:bg-gray-50 hover:border-[#FFD100] transition-all cursor-pointer relative"
               @dragover.prevent @drop.prevent="handleFileDrop">
            <input type="file" multiple accept="image/jpeg,image/png,image/webp" @change="handleFileDrop" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
            <UploadCloud class="mx-auto text-gray-400 mb-2" :size="32"/>
            <p class="text-sm font-bold text-gray-900">Glissez ou cliquez</p>
            <p class="text-[10px] font-semibold text-gray-400 uppercase mt-1">JPG, PNG, WEBP (Max 5 Mo)</p>
          </div>

          <!-- Existing Images -->
          <div v-if="existingImages.length" class="mt-4 grid grid-cols-2 gap-3">
            <div v-for="img in existingImages" :key="img.id" class="relative group rounded-xl overflow-hidden aspect-[4/3] bg-gray-100">
              <img :src="img.path" class="w-full h-full object-cover">
              <button @click.prevent="pendingDeleteId=img.id" type="button" class="absolute top-1.5 right-1.5 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity" aria-label="Supprimer l'image">
                <X :size="12"/>
              </button>
            </div>
          </div>

          <!-- New Previews -->
          <div v-if="imagePreviews.length" class="mt-4 grid grid-cols-2 gap-3">
            <div v-for="(preview, index) in imagePreviews" :key="index" class="relative group rounded-xl overflow-hidden aspect-[4/3] border-2 border-[#FFD100]">
              <img :src="preview.url" class="w-full h-full object-cover">
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <button @click.prevent="removePreview(index)" type="button" class="w-8 h-8 bg-white text-red-500 rounded-full flex items-center justify-center shadow-lg hover:bg-red-50 hover:scale-110 transition-all">
                  <X :size="16"/>
                </button>
              </div>
              <span v-if="index===0 && !existingImages.length" class="absolute bottom-1.5 left-1.5 bg-[#FFD100] text-black text-[9px] font-black uppercase px-2 py-0.5 rounded-md">Principal</span>
            </div>
          </div>

        </div>

      </div>
    </form>
    <div v-if="pendingDeleteId" class="fixed inset-0 z-50 bg-black/40 grid place-items-center p-4" @click.self="pendingDeleteId=null"><div class="bg-white rounded-3xl p-6 max-w-md w-full space-y-4"><h3 class="text-lg font-black">Supprimer cette image ?</h3><p class="text-sm text-gray-600">L’image sera retirée définitivement de la galerie du véhicule.</p><div class="flex justify-end gap-2"><button @click="pendingDeleteId=null" class="border rounded-xl px-4 py-2 font-bold">Annuler</button><button @click="removeExistingImage(pendingDeleteId)" class="bg-red-600 text-white rounded-xl px-4 py-2 font-bold">Supprimer</button></div></div></div>
  </div>
</template>
