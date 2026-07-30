import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './Router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import { bootstrapAuth, isEPortalSharedAuth } from './Composables/useAuth'
import { useCompany } from './Composables/useCompany.js'

const { applyCompanyBranding } = useCompany()

function mountApp() {
  createApp(App)
    .use(router)
    .use(ElementPlus)
    .mount('#app')
}

async function startApp() {
  if (!isEPortalSharedAuth()) {
    await bootstrapAuth()
  }
  mountApp()
  applyCompanyBranding()
}

// E-Portal SSO: mount immediately so the loader is visible during auth.
if (isEPortalSharedAuth()) {
  mountApp()
  applyCompanyBranding()
} else {
  startApp()
}
