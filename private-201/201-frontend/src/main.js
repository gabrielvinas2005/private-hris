import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './Router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import { useCompany } from '@/composable/useCompany.js'

const { applyCompanyBranding } = useCompany()

const app = createApp(App)
app.config.devtools = true

applyCompanyBranding()

app
    .use(router)
    .use(ElementPlus)
    .mount('#app')