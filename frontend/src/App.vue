<!-- Root Vue component. Page layouts live in route views, so this file only renders the active route. -->
<script setup>
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()

// Confirm the saved token and refresh user data when the application starts.
onMounted(() => auth.restore())
</script>
<template>
  <RouterView />
</template>
