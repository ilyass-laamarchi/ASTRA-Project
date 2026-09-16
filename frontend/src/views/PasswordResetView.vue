<!-- Password recovery view for requesting an email or applying a Laravel reset token. -->
<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
const route = useRoute()
const router = useRouter()
const email = ref(String(route.query.email || ''))
const password = ref('')
const confirmation = ref('')
const message = ref('')
const error = ref('')
const busy = ref(false)
const resetting = computed(() => Boolean(route.query.token))

/** Requests a reset link or submits a token with the replacement password. */
async function submit() {
  busy.value = true
  error.value = ''
  try {
    if (resetting.value) {
      await api.post('/reset-password', {
        token: route.query.token,
        email: email.value,
        password: password.value,
        password_confirmation: confirmation.value,
      })
      message.value = 'Mot de passe réinitialisé.'
      setTimeout(() => router.push('/login'), 900)
    } else {
      const result = await api.post('/forgot-password', { email: email.value })
      message.value = result.message || result.data?.message
    }
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Demande impossible.'
  } finally {
    busy.value = false
  }
}
</script>
<template><main class="min-h-screen grid place-items-center bg-gray-50 p-4"><form @submit.prevent="submit" class="w-full max-w-md bg-white border rounded-3xl p-8 space-y-4"><RouterLink to="/login" class="text-sm font-bold">← Connexion</RouterLink><h1 class="text-2xl font-black">{{resetting?'Nouveau mot de passe':'Mot de passe oublié'}}</h1><p class="text-sm text-gray-500">{{resetting?'Choisissez un nouveau mot de passe sécurisé.':'Saisissez votre e-mail pour recevoir un lien de réinitialisation.'}}</p><input v-model="email" required type="email" placeholder="nom@exemple.com" class="w-full border rounded-xl px-4 py-3"><template v-if="resetting"><input v-model="password" required minlength="10" type="password" placeholder="Nouveau mot de passe" class="w-full border rounded-xl px-4 py-3"><input v-model="confirmation" required type="password" placeholder="Confirmer le mot de passe" class="w-full border rounded-xl px-4 py-3"></template><p v-if="message" class="text-sm font-bold text-emerald-700">{{message}}</p><p v-if="error" class="text-sm font-bold text-red-700">{{error}}</p><button :disabled="busy" class="w-full bg-gray-900 text-white rounded-xl py-3 font-bold">{{busy?'Patientez…':(resetting?'Réinitialiser':'Envoyer le lien')}}</button></form></main></template>
