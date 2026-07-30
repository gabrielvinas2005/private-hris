import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools({
      launchEditor: 'cursor',
    }),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  define: {
    // Define environment variables for development
    'import.meta.env.VITE_DEV_AUTH_ENABLED': JSON.stringify(process.env.NODE_ENV === 'development' ? 'true' : 'false'),
  },
})
