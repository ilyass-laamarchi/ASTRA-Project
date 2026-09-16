<!-- Central ASTRA logo component. It selects the readable transparent asset for light or dark surfaces. -->
<script setup>
defineProps({
  alt: {
    type: String,
    default: 'ASTRA — Mobility, Service, Confiance',
  },
  loading: {
    type: String,
    default: 'eager',
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'inverse'].includes(value),
  },
})
</script>

<template>
  <img
    :src="variant === 'inverse'
      ? '/assets/brand/astra-logo-official-white-blue-transparent.png'
      : '/assets/brand/astra-logo-official-transparent.png'"
    :alt="alt"
    :loading="loading"
    decoding="async"
  />
</template>
