/** Unit-test environment for Vue and router behavior. */
import {defineConfig} from 'vitest/config';import vue from '@vitejs/plugin-vue';export default defineConfig({plugins:[vue()],test:{environment:'jsdom',include:['src/**/*.test.js']}})
