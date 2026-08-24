<template>
  <div class="relative min-h-screen overflow-hidden bg-slate-950">
    <!-- Background -->
    <div
      class="absolute inset-0 bg-center bg-cover opacity-30"
      style="background-image: url('../assets/img/hr_bgb.png')"
    />
    <div class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-blue-950/80 to-slate-900/90" />

    <!-- Ambient glow -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-blue-600/20 rounded-full blur-[120px] pointer-events-none" />
    <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-cyan-500/10 rounded-full blur-[100px] pointer-events-none" />

    <!-- Content -->
    <div class="relative z-10 flex items-center justify-center min-h-screen px-4 py-10">
      <div class="w-full max-w-lg">
        <!-- Card -->
        <div class="overflow-hidden border shadow-2xl bg-white/95 backdrop-blur-md rounded-2xl border-white/20">
          <!-- Brand header strip -->
          <div class="px-8 pt-10 pb-6 text-center ">
            <div v-if="companyLogo" class="inline-flex items-center justify-center w-28 h-28 mb-5 overflow-hidden rounded-2xl">
              <img
                :src="companyLogo"
                :alt="company.name ? `${company.name} logo` : 'Company logo'"
                class="object-contain w-full h-full p-2"
              />
            </div>

            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
              {{ company.name || 'Employee Portal' }}
            </h1>

            <p class="mt-2 text-sm font-medium tracking-wide text-blue-700 uppercase">
              Human Resource Management Platform
            </p>
          </div>

          <!-- Details -->
          <div class="px-8 pb-8 space-y-5">
            <div v-if="company.address" class="flex items-start gap-3 text-left">
              <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 mt-0.5 bg-blue-50 rounded-lg">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </div>
              <p class="text-sm leading-relaxed text-slate-600">{{ company.address }}</p>
            </div>

            <div v-if="company.email || company.telephone_no || company.mobile_no" class="flex flex-wrap gap-3 pt-1">
              <div v-if="company.email" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs text-slate-600 bg-slate-50 rounded-full border border-slate-200">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                {{ company.email }}
              </div>
              <div v-if="company.telephone_no" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs text-slate-600 bg-slate-50 rounded-full border border-slate-200">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                {{ company.telephone_no }}
              </div>
              <div v-if="company.mobile_no" class="inline-flex items-center gap-2 px-3 py-1.5 text-xs text-slate-600 bg-slate-50 rounded-full border border-slate-200">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                {{ company.mobile_no }}
              </div>
            </div>

            <div class="pt-2">
              <button
                type="button"
                @click="goToLogin"
                class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-sm font-semibold text-white transition-all duration-200 bg-blue-600 rounded-xl hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-600/25 active:scale-[0.98]"
              >
                Sign In to Portal
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <p class="mt-6 text-xs text-center text-slate-500">
          Secure employee self-service portal
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import { fetchCompanyPublic, getCompanyLogo } from '../services/companyPublic.js'

export default {
  name: 'Home',
  data() {
    return {
      company: {
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo_data_url: null
      }
    }
  },
  computed: {
    companyLogo() {
      return getCompanyLogo(this.company)
    }
  },
  async mounted() {
    this.company = await fetchCompanyPublic()
  },
  methods: {
    goToLogin() {
      this.$router.push('/login')
    }
  }
}
</script>

<style scoped>
::-webkit-scrollbar {
  display: none;
}

* {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
