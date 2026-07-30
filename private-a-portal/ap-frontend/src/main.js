import { createApp } from "vue";
import ElementPlus from "element-plus";
import "element-plus/dist/index.css";
import App from "./App.vue";
import router from "./router";
import "./assets/style.css";
import axios from "axios";

// Configure axios
axios.defaults.baseURL =
  import.meta.env.VITE_API_URL || "http://localhost:8000/api";
axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Add token to requests if available
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle response errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("user_data");
      router.push("/login");
    }
    return Promise.reject(error);
  }
);

import { applyCompanyBranding } from "./services/companyPublic.js";

const app = createApp(App);
app.use(ElementPlus);

// Add axios to global properties
app.config.globalProperties.$axios = axios;

app.use(router);
app.mount("#app");
applyCompanyBranding();
