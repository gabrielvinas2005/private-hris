import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import './assets/style.css'
import axios from 'axios'
import { applyCompanyBranding } from './services/companyPublic.js'
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

// Configure axios
axios.defaults.baseURL = import.meta.env.VITE_API_URL || ''
axios.defaults.timeout = 15000
axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Add token to requests if available
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

// Handle response errors
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token')
            localStorage.removeItem('temp_token')
            localStorage.removeItem('user_data')
            localStorage.removeItem('user_tab_access')
            router.push('/login')
        }
        return Promise.reject(error)
    }
)

const app = createApp(App)

// Add axios to global properties
app.config.globalProperties.$http = axios

// Configure toast
const toastOptions = {
    position: 'top-right',
    timeout: 5000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false
}

app.use(router)
app.use(Toast, toastOptions)
app.use(ElementPlus)

// Register all Element Plus icons
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(key, component)
}

applyCompanyBranding()
app.mount('#app')
