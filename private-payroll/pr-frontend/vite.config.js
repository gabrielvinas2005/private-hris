import { fileURLToPath, URL } from "node:url";

import { defineConfig } from "vite";
import AutoImport from "unplugin-auto-import/vite";
import Components from "unplugin-vue-components/vite";
import { ElementPlusResolver } from "unplugin-vue-components/resolvers";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools({ launchEditor: 'cursor' }),
    AutoImport({
      resolvers: [ElementPlusResolver()],
    }),
    Components({
      resolvers: [ElementPlusResolver()],
      // Keep duplicate filenames safe by namespacing from directory path
      // e.g. rptOvertimePayment/ReportParameters.vue -> RptOvertimePaymentReportParameters
      directoryAsNamespace: true,
      collapseSamePrefixes: true,
    }),
  ],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
    css: {
      preprocessorOptions: {
        scss: { api: "modern-compiler" },
      },
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      "/api": {
        // Update to the backend port actually running (matches src/config/api.js)
        target: "http://localhost:8003",
        changeOrigin: true,
        secure: false,
      },
    },
  },
});
