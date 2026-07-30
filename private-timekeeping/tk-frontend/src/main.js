import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './Router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import './plugins/toastBridge'
import { useCompany } from './Composables/useCompany.js'

const { applyCompanyBranding } = useCompany()

createApp(App)
    .use(router)
    .use(ElementPlus)
    .mount('#app')

applyCompanyBranding()
