/** Vue entry point: registers state, routing, and the global design system. */
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './style.css'
createApp(App).use(createPinia()).use(router).mount('#app')
