/**
 * Vite development and production build configuration for the Vue application.
 * Configures Vue plugin and local development proxy for API and storage requests.
 */
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    // A temporary reverse proxy can opt into its Docker hostname without
    // disabling Vite's host-header protection for ordinary local development.
    ...(process.env.VITE_ALLOWED_HOST ? { allowedHosts: [process.env.VITE_ALLOWED_HOST] } : {}),
    proxy: {
      '/api': {
        target: process.env.VITE_API_TARGET || 'http://api:8000',
        changeOrigin: true,
      },
      '/storage': {
        target: process.env.VITE_API_TARGET || 'http://api:8000',
        changeOrigin: true,
      },
    },
  },
})
