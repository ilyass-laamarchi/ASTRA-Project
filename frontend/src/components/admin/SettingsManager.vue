<!-- Shared profile/preferences view with administrator-only agency and Stripe status panels. -->
<script setup>
import { computed, onMounted, ref } from 'vue'
import { Bell, Building, CreditCard, Shield, User } from 'lucide-vue-next'
import api from '../../lib/api'
import { publicMediaUrl } from '../../lib/media'
import { useAuthStore } from '../../stores/auth'

const auth = useAuthStore()
const activeTab = ref('profile')
const busy = ref(false)
const message = ref('')
const error = ref('')
const profile = ref({ first_name: '', last_name: '', email: '', phone: '' })
const password = ref({ current_password: '', password: '', password_confirmation: '' })
const preferences = ref({ reservations: true, payments: true, system: true })
const system = ref({ agency_name: '', agency_email: '', agency_phone: '', agency_address: '', currency: 'MAD', timezone: 'Africa/Casablanca', booking_notice_hours: 24 })
const stripe = ref({ configured: false, mode: 'test', publishable_key: null, webhook_configured: false, message: '' })
const isAdmin = computed(() => auth.user?.role === 'admin')
const failedAvatarUrl = ref('')
const avatarUrl = computed(() => publicMediaUrl(auth.user?.avatar_path))
const avatarAvailable = computed(() => Boolean(avatarUrl.value) && failedAvatarUrl.value !== avatarUrl.value)

// Remember a failed URL so the image is not retried on every render.
function markAvatarFailed(event) {
  failedAvatarUrl.value = event.currentTarget.currentSrc || event.currentTarget.src
}

// Display one consistent success or error message for settings actions.
function feedback(ok, text) {
  error.value = ok ? '' : text
  message.value = ok ? text : ''
}

// Hydrate personal preferences and administrator-only system information.
async function load() {
  Object.assign(profile.value, auth.user || {})
  try {
    const result = await api.get('/notification-preferences')
    Object.assign(preferences.value, result.data)
  } catch {
    feedback(false, 'Chargement des préférences impossible.')
  }

  if (!isAdmin.value) return

  try {
    const [settingsResult, paymentStatusResult] = await Promise.all([
      api.get('/admin/settings'),
      api.get('/admin/settings/payment-status'),
    ])
    Object.assign(system.value, settingsResult.data)
    Object.assign(stripe.value, paymentStatusResult.data)
  } catch {
    feedback(false, 'Chargement de la configuration impossible.')
  }
}

// Persist the current user's editable profile fields.
async function saveProfile() {
  busy.value = true
  try {
    const result = await api.patch('/profile', profile.value)
    auth.updateUser(result.user || result.data?.user)
    feedback(true, 'Profil enregistré.')
  } catch (requestError) {
    feedback(false, requestError.response?.data?.message || 'Profil non enregistré.')
  } finally {
    busy.value = false
  }
}

// Upload an avatar and immediately refresh the shared authenticated user.
async function uploadAvatar(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const data = new FormData()
  data.append('avatar', file)
  busy.value = true
  try {
    const result = await api.post('/profile/avatar', data, { headers: { 'Content-Type': 'multipart/form-data' } })
    failedAvatarUrl.value = ''
    auth.updateUser(result.user || result.data?.user)
    feedback(true, 'Photo mise à jour.')
  } catch (requestError) {
    feedback(false, requestError.response?.data?.message || 'Photo non enregistrée.')
  } finally {
    busy.value = false
  }
}

// Change the password after server-side current-password verification.
async function savePassword() {
  busy.value = true
  try {
    await api.put('/password', password.value)
    password.value = { current_password: '', password: '', password_confirmation: '' }
    feedback(true, 'Mot de passe mis à jour.')
  } catch (requestError) {
    feedback(false, requestError.response?.data?.message || 'Mot de passe non modifié.')
  } finally {
    busy.value = false
  }
}

// Save notification preferences for the signed-in user.
async function savePreferences() {
  busy.value = true
  try {
    await api.put('/notification-preferences', preferences.value)
    feedback(true, 'Préférences enregistrées.')
  } catch (requestError) {
    feedback(false, requestError.response?.data?.message || 'Préférences non enregistrées.')
  } finally {
    busy.value = false
  }
}

// Save administrator-controlled agency and booking settings.
async function saveSystem() {
  busy.value = true
  try {
    const result = await api.put('/admin/settings', system.value)
    Object.assign(system.value, result.data)
    feedback(true, 'Configuration de l’agence enregistrée.')
  } catch (requestError) {
    feedback(false, requestError.response?.data?.message || 'Configuration non enregistrée.')
  } finally {
    busy.value = false
  }
}
onMounted(load)
</script>

<template>
 <div class="max-w-5xl"><div class="mb-7"><h2 class="text-2xl font-black">Paramètres</h2><p class="text-sm font-medium text-gray-500">Gérez votre compte et la configuration ASTRA.</p></div>
  <p v-if="message" class="mb-4 p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-bold">{{message}}</p><p v-if="error" class="mb-4 p-3 rounded-xl bg-red-50 text-red-700 text-sm font-bold">{{error}}</p>
  <div class="flex flex-col md:flex-row gap-7"><nav class="md:w-60 space-y-1">
   <button v-for="tab in [{id:'profile',label:'Mon profil',icon:User},{id:'notifications',label:'Notifications',icon:Bell},{id:'security',label:'Sécurité',icon:Shield},...(isAdmin?[{id:'system',label:'Agence & système',icon:Building},{id:'stripe',label:'Configuration Stripe',icon:CreditCard}]:[])]" :key="tab.id" @click="activeTab=tab.id" :class="['w-full flex gap-3 px-4 py-3 rounded-xl text-sm font-bold',activeTab===tab.id?'bg-gray-900 text-white':'hover:bg-gray-50 text-gray-600']"><component :is="tab.icon" :size="18"/>{{tab.label}}</button>
  </nav><main class="flex-1 bg-white border border-gray-100 rounded-3xl p-6 md:p-8">
   <form v-if="activeTab==='profile'" @submit.prevent="saveProfile" class="space-y-5"><h3 class="text-lg font-black">Informations personnelles</h3><div class="flex items-center gap-4"><img v-if="avatarAvailable" :src="avatarUrl" class="w-16 h-16 rounded-full object-cover" alt="Photo de profil" @error="markAvatarFailed"><div v-else class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center"><User/></div><label class="px-4 py-2 border rounded-lg text-xs font-bold cursor-pointer">Changer la photo<input type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadAvatar"></label></div>
    <div class="grid md:grid-cols-2 gap-4"><label class="text-xs font-bold">Prénom<input v-model="profile.first_name" required class="mt-2 w-full border rounded-xl px-4 py-3 text-sm"></label><label class="text-xs font-bold">Nom<input v-model="profile.last_name" required class="mt-2 w-full border rounded-xl px-4 py-3 text-sm"></label><label class="text-xs font-bold">E-mail<input v-model="profile.email" required type="email" class="mt-2 w-full border rounded-xl px-4 py-3 text-sm"></label><label class="text-xs font-bold">Téléphone<input v-model="profile.phone" class="mt-2 w-full border rounded-xl px-4 py-3 text-sm"></label></div><button :disabled="busy" class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold">Enregistrer les modifications</button></form>
   <form v-else-if="activeTab==='notifications'" @submit.prevent="savePreferences" class="space-y-5"><h3 class="text-lg font-black">Préférences de notification</h3><label v-for="item in [{id:'reservations',label:'Réservations et changements de statut'},{id:'payments',label:'Paiements et remboursements'},{id:'system',label:'Informations système'}]" :key="item.id" class="flex justify-between border rounded-xl p-4 font-bold text-sm"><span>{{item.label}}</span><input v-model="preferences[item.id]" type="checkbox" class="w-5 h-5"></label><button :disabled="busy" class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold">Enregistrer</button></form>
   <form v-else-if="activeTab==='security'" @submit.prevent="savePassword" class="space-y-4"><h3 class="text-lg font-black">Changer le mot de passe</h3><input v-model="password.current_password" required type="password" placeholder="Mot de passe actuel" class="w-full border rounded-xl px-4 py-3"><input v-model="password.password" required minlength="10" type="password" placeholder="Nouveau mot de passe" class="w-full border rounded-xl px-4 py-3"><input v-model="password.password_confirmation" required type="password" placeholder="Confirmer le nouveau mot de passe" class="w-full border rounded-xl px-4 py-3"><button :disabled="busy" class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold">Mettre à jour</button></form>
   <form v-else-if="activeTab==='system'" @submit.prevent="saveSystem" class="space-y-4"><h3 class="text-lg font-black">Agence & système</h3><div class="grid md:grid-cols-2 gap-4"><input v-model="system.agency_name" required placeholder="Nom de l'agence" class="border rounded-xl px-4 py-3"><input v-model="system.agency_email" required type="email" placeholder="E-mail" class="border rounded-xl px-4 py-3"><input v-model="system.agency_phone" required placeholder="Téléphone" class="border rounded-xl px-4 py-3"><input v-model="system.agency_address" required placeholder="Adresse" class="border rounded-xl px-4 py-3"><select v-model="system.timezone" class="border rounded-xl px-4 py-3"><option value="Africa/Casablanca">Africa/Casablanca</option></select><input v-model.number="system.booking_notice_hours" type="number" min="0" max="168" class="border rounded-xl px-4 py-3" aria-label="Préavis de réservation en heures"></div><button :disabled="busy" class="bg-gray-900 text-white px-6 py-3 rounded-xl font-bold">Enregistrer</button></form>
   <div v-else-if="activeTab==='stripe'" class="space-y-5"><div class="flex justify-between"><h3 class="text-lg font-black">Configuration Stripe</h3><span :class="['text-xs font-black px-3 py-1 rounded-full',stripe.configured?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700']">{{stripe.configured?'Configuré':'Configuration requise'}}</span></div><dl class="grid grid-cols-2 gap-4 text-sm"><div><dt class="text-gray-500">Mode</dt><dd class="font-black">{{stripe.mode}}</dd></div><div><dt class="text-gray-500">Clé publique</dt><dd class="font-mono">{{stripe.publishable_key||'Non configurée'}}</dd></div><div><dt class="text-gray-500">Webhook</dt><dd class="font-black">{{stripe.webhook_configured?'Configuré':'Non configuré'}}</dd></div></dl><p class="bg-gray-50 border rounded-xl p-4 text-sm">{{stripe.message}} Les secrets restent exclusivement dans les variables d’environnement du serveur.</p></div>
  </main></div>
 </div>
</template>
