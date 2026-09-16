<!-- Completes Google OAuth by storing Laravel's redirected token and loading the user. -->
<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../lib/api'
import { useAuthStore } from '../stores/auth'
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const error = ref('')

/** Validates the redirected token with Laravel, stores the user, and opens their dashboard. */
async function finishOAuth() {
  const token = String(route.query.token || '')
  if (!token) {
    error.value = 'Jeton Google manquant.'
    return
  }
  localStorage.setItem('astra_token', token)
  try {
    const { data } = await api.get('/me')
    auth.updateUser(data.user)
    router.replace(`/${data.user.role}/dashboard`)
  } catch {
    localStorage.removeItem('astra_token')
    error.value = 'Connexion Google impossible.'
  }
}

onMounted(finishOAuth)
</script>
<template><main class="min-h-screen grid place-items-center bg-gray-50"><div class="bg-white border rounded-3xl p-10 text-center"><h1 class="text-xl font-black">Connexion Google</h1><p class="mt-3 text-sm text-gray-500">{{error || 'Finalisation de votre session…'}}</p><RouterLink v-if="error" to="/login" class="inline-block mt-5 underline font-bold">Retour à la connexion</RouterLink></div></main></template>
