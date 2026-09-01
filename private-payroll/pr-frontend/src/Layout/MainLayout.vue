<template>
  <div class="flex min-h-screen bg-[#F3F5FA] text-slate-900 font-sans antialiased">
    <aside :class="[
      collapsed ? 'w-20' : 'w-64 sm:w-72',
      'bg-white text-slate-700 border-slate-200/70 shadow-xl shadow-slate-200/50',
      'h-screen sticky top-0 z-40 flex flex-col transition-all duration-300 ease-in-out border-r overflow-y-auto'
    ]">
      <!-- Sidebar Header -->
      <div class="px-6 py-8 border-b border-slate-200/70">
        <div class="flex flex-col items-center text-center space-y-3">
          <div v-if="showLogo && companyLogoSrc" class="flex items-center justify-center overflow-hidden w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200/70">
            <img :src="companyLogoSrc" :alt="companyName" class="object-contain w-full h-full" @error="onLogoError" />
          </div>
          <div v-else class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-lg tracking-tight shadow-lg shadow-[#3B5EFF]/30">
            PR
          </div>
          <div v-if="!collapsed" class="min-w-0 w-full">
            <h1 class="text-base font-bold tracking-tight text-slate-900 truncate">{{ companyName }}</h1>
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 truncate mt-0.5">Payroll Module</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3.5 py-5 space-y-1 overflow-y-auto sidebar-scroll">
        <!-- Dashboard -->
        <RouterLink
          v-if="navVisible(PAYROLL_ROUTE_ACCESS.payroll)"
          to="/"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
          :class="[collapsed ? 'justify-center px-0' : 'px-3.5', $route.path === '/' ? activeClass : inactiveClass]"
        >
          <el-icon class="text-lg flex-shrink-0"><Money /></el-icon>
          <span v-if="!collapsed">Payroll Dashboard</span>
        </RouterLink>

        <!-- Section Label: Core Setup -->
        <p v-if="!collapsed && showCoreSetupNav" class="px-3.5 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Core Setup</p>

        <template v-if="showCoreSetupNav">
          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-period'])" to="/payroll-period" :class="navClass('/payroll-period')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><CalendarClock /></el-icon>
            <span v-if="!collapsed">Payroll Period</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-item-schedule'])" to="/payroll-item-schedule" :class="navClass('/payroll-item-schedule')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><ClipboardList /></el-icon>
            <span v-if="!collapsed">Item Schedule</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['income-deduction'])" to="/income-deduction" :class="navClass('/income-deduction')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><BanknoteArrowUp /></el-icon>
            <span v-if="!collapsed">Income & Deduction</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['hdmf-premium'])" to="/hdmf-premium" :class="navClass('/hdmf-premium')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><ReceiptText /></el-icon>
            <span v-if="!collapsed">Pag-IBIG Premium</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['loan-application'])" to="/loan-application" :class="navClass('/loan-application')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><FileSignature /></el-icon>
            <span v-if="!collapsed">Loan Management</span>
          </RouterLink>
        </template>

        <!-- Section Label: Payroll Execution -->
        <p v-if="!collapsed && showExecutionNav" class="px-3.5 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Payroll Execution</p>

        <template v-if="showExecutionNav">
          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-process'])" to="/payroll-process" :class="navClass('/payroll-process')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><Money /></el-icon>
            <span v-if="!collapsed">Payroll Process</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['thirteenth-month-pay'])" to="/thirteenth-month-pay" :class="navClass('/thirteenth-month-pay')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><CalendarDays /></el-icon>
            <span v-if="!collapsed">13th Month Pay</span>
          </RouterLink>

          <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['final-pay'])" to="/final-pay" :class="navClass('/final-pay')" class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group">
            <el-icon class="text-lg flex-shrink-0"><CircleCheckBig /></el-icon>
            <span v-if="!collapsed">Final Pay</span>
          </RouterLink>
        </template>

        <!-- Bonuses & Overtime -->
        <p v-if="!collapsed && showBonusesSection" class="px-3.5 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Bonuses & Overtime</p>

        <RouterLink
          v-if="showBonusesSection"
          to="/payroll-bonuses"
          :class="[collapsed ? 'justify-center px-0' : 'px-3.5', isBonusesActive ? activeClass : inactiveClass]"
          class="flex items-center py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
        >
          <el-icon class="text-lg flex-shrink-0"><Gift /></el-icon>
          <span v-if="!collapsed">Bonuses & Overtime</span>
        </RouterLink>

        <!-- Reports Accordion -->
        <div v-if="showReportsSection" class="pt-1">
          <p v-if="!collapsed" class="px-3.5 pt-3 pb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Reports</p>
          <button
            @click="toggleReports"
            class="w-full flex items-center justify-between py-2.5 space-x-3 text-[13.5px] font-medium transition-all duration-200 rounded-xl group"
            :class="[collapsed ? 'justify-center px-0' : 'px-3.5', isReportsActive ? 'text-[#3B5EFF] bg-[#3B5EFF]/[0.07] font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100']"
          >
            <div class="flex items-center space-x-3 min-w-0">
              <el-icon class="text-lg flex-shrink-0"><FileBarChart2 /></el-icon>
              <span v-if="!collapsed" class="truncate">Payroll Reports</span>
            </div>
            <svg v-if="!collapsed" class="w-4 h-4 transition-transform duration-200 flex-shrink-0" :class="{ 'rotate-180': openReports }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <el-collapse-transition>
            <div v-show="!collapsed && openReports" class="pl-3 pr-1 mt-1 space-y-1 border-l-2 border-slate-200/70 ml-4">
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-summary-report'])" to="/payroll-summary-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/payroll-summary-report' ? subActiveClass : subInactiveClass]"><el-icon><FileBarChart2 /></el-icon><span>Payroll Summary</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['payslip-report'])" to="/payslip-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/payslip-report' ? subActiveClass : subInactiveClass]"><el-icon><Receipt /></el-icon><span>Payslip</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['bank-remittance-report'])" to="/bank-remittance-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/bank-remittance-report' ? subActiveClass : subInactiveClass]"><el-icon><Building2 /></el-icon><span>Bank Remittance</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['sss-contribution-report'])" to="/sss-contribution-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/sss-contribution-report' ? subActiveClass : subInactiveClass]"><el-icon><FileText /></el-icon><span>SSS Contribution</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['philhealth-remittance-report'])" to="/philhealth-remittance-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/philhealth-remittance-report' ? subActiveClass : subInactiveClass]"><el-icon><FileText /></el-icon><span>PhilHealth Remittance</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['pag-ibig-contribution-report'])" to="/pag-ibig-contribution-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/pag-ibig-contribution-report' ? subActiveClass : subInactiveClass]"><el-icon><ReceiptText /></el-icon><span>Pag-IBIG Contribution</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['pag-ibig-loan-report'])" to="/pag-ibig-loan-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/pag-ibig-loan-report' ? subActiveClass : subInactiveClass]"><el-icon><FileSignature /></el-icon><span>Pag-IBIG Loan</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['overtime-payment-report'])" to="/overtime-payment-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/overtime-payment-report' ? subActiveClass : subInactiveClass]"><el-icon><Clock /></el-icon><span>Overtime Payment</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['mid-year-bonus-report'])" to="/mid-year-bonus-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/mid-year-bonus-report' ? subActiveClass : subInactiveClass]"><el-icon><Gift /></el-icon><span>Mid-Year Bonus</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['year-end-bonus-report'])" to="/year-end-bonus-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/year-end-bonus-report' ? subActiveClass : subInactiveClass]"><el-icon><Gift /></el-icon><span>Year-End Bonus</span></RouterLink>
              <RouterLink v-if="navVisible(PAYROLL_ROUTE_ACCESS['extra-bonus-report'])" to="/extra-bonus-report" class="flex items-center py-2 px-3 space-x-2.5 text-xs font-medium rounded-lg transition-all" :class="[route.path === '/extra-bonus-report' ? subActiveClass : subInactiveClass]"><el-icon><Sparkles /></el-icon><span>Other Bonuses</span></RouterLink>
            </div>
          </el-collapse-transition>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-slate-200/70 bg-slate-50/80">
        <div class="flex items-center" :class="collapsed ? 'justify-center' : 'justify-between'">
          <button @click="collapsed = !collapsed" class="p-2 transition rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-200/60" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
            <svg v-if="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
          <div v-if="!collapsed" class="text-right">
            <p class="text-xs font-medium text-slate-600">{{ companyName }}</p>
            <p class="text-[11px] text-slate-400">v2.0.0</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 min-w-0">
      <header class="sticky top-0 z-30 px-4 py-3 border-b border-slate-200/70 shadow-sm bg-white/90 backdrop-blur sm:px-6">
        <div class="mt-2 mb-2 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
              <slot name="header">Payroll Module</slot>
            </h2>
            <p class="mt-0.5 text-sm text-slate-500">Private Sector Payroll Computation & Statutory Remittance Management</p>
          </div>
          <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            <button @click="toggleTheme" class="p-2 text-slate-500 transition-all duration-200 rounded-xl hover:text-slate-900 hover:bg-slate-100" :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
              <svg v-if="!isDarkMode" class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
              <svg v-else class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </button>
            <div class="flex items-center px-3.5 py-1.5 border rounded-2xl space-x-2.5 bg-slate-50/80 border-slate-200/70">
              <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gradient-to-br from-[#4A6CFB] to-[#22308F] text-white font-bold text-xs shadow-sm shadow-[#3B5EFF]/30">{{ userInitials }}</div>
              <div class="text-left hidden sm:block">
                <p class="text-xs font-semibold leading-tight truncate max-w-[120px] text-slate-800">{{ userDisplayName }}</p>
                <p class="text-[10px] font-medium leading-tight truncate max-w-[120px] text-slate-500">{{ userDisplayEmail || 'Payroll Officer' }}</p>
              </div>
            </div>
            <button @click="handleLogout" :disabled="loading" class="flex items-center px-3 py-2 space-x-2 text-sm font-medium text-slate-600 transition-all duration-200 rounded-lg hover:text-red-600 hover:bg-red-50">
              <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
              <span class="hidden sm:inline">Sign Out</span>
            </button>
          </div>
        </div>
      </header>
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-6 overflow-y-auto bg-[#F3F5FA]">
        <div class="p-4 bg-white border border-slate-200/70 shadow-sm rounded-2xl sm:p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { ElIcon, ElCollapseTransition } from "element-plus";
import { Money } from "@element-plus/icons-vue";
import {
  CalendarClock, ClipboardList, FileSignature, Gift, FileBarChart2,
  Clock, FileText, Receipt, Building2, FileSpreadsheet, UserCircle,
  ReceiptText, BanknoteArrowUp, Sparkles, CalendarDays, CircleCheckBig,
} from "lucide-vue-next";
import { useAuth, ensurePayrollAccessUser } from "@/Composables/useAuth";
import { useCompany } from "@/Composables/useCompany.js";
import {
  PAYROLL_ROUTE_ACCESS, payrollNavVisible,
  CORE_SETUP_NAV_NAMES, PAYROLL_EXECUTION_NAV_NAMES,
  BENEFIT_ROUTE_NAMES, REPORT_ROUTE_NAMES,
  showAnyNamedRoutes, canSeeBonusesSection,
} from "@/config/payrollAccess";

const activeClass = "bg-gradient-to-r from-[#3B5EFF] to-[#2946D9] text-white font-semibold shadow-lg shadow-[#3B5EFF]/25 px-3.5";
const inactiveClass = "text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3.5";
const subActiveClass = "bg-[#3B5EFF] text-white font-semibold shadow-md shadow-[#3B5EFF]/20";
const subInactiveClass = "text-slate-600 hover:text-slate-900 hover:bg-slate-100";

const route = useRoute();
const { logout, loading, user, getCurrentUser, checkAuth } = useAuth();
const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany();

const showLogo = ref(true);
const collapsed = ref(false);
const openReports = ref(false);
const isDarkMode = ref(localStorage.getItem("theme") === "dark");

const companyName = computed(() => primaryCompany.value?.name || "Payroll Module");
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value));

function onLogoError() { showLogo.value = false; }
watch(primaryCompany, () => { showLogo.value = true; });

const navVisible = (spec) => payrollNavVisible(user.value, spec);

function navClass(prefix) {
  const isActive = route.path.startsWith(prefix);
  return [collapsed.value ? "justify-center px-0" : "", isActive ? activeClass : inactiveClass];
}

const showCoreSetupNav = computed(() => showAnyNamedRoutes(user.value, CORE_SETUP_NAV_NAMES));
const showExecutionNav = computed(() => showAnyNamedRoutes(user.value, PAYROLL_EXECUTION_NAV_NAMES));
const showBonusesSection = computed(() => canSeeBonusesSection(user.value));
const showReportsSection = computed(() => navVisible({ keys: ["payroll_reports", "payroll_report"] }));

const isReportsActive = computed(() => route.name ? REPORT_ROUTE_NAMES.includes(route.name) : false);
const isBonusesActive = computed(() => route.path.startsWith("/payroll-bonuses"));

function toggleReports() { openReports.value = !openReports.value; }

const userInitials = computed(() => {
  if (user.value?.name) return user.value.name.split(" ").filter(Boolean).map((w) => w[0].toUpperCase()).slice(0, 2).join("");
  if (user.value?.email) return user.value.email[0].toUpperCase();
  return "PR";
});
const userDisplayName = computed(() => user.value?.name || user.value?.email?.split("@")[0] || "Payroll Officer");
const userDisplayEmail = computed(() => user.value?.email || "");

const handleLogout = async () => {
  await logout();
  window.dispatchEvent(new CustomEvent("user-logout"));
};

const toggleTheme = () => {
  isDarkMode.value = !isDarkMode.value;
  localStorage.setItem("theme", isDarkMode.value ? "dark" : "light");
  document.documentElement.classList.toggle("dark", isDarkMode.value);
};

onMounted(async () => {
  if (isDarkMode.value) document.documentElement.classList.add("dark");
  if (!checkAuth()) return;
  try {
    await ensurePayrollAccessUser();
    if (!user.value?.email) await getCurrentUser();
    await fetchCompanies();
  } catch (e) { console.error(e); }
});

watch(isReportsActive, (active) => { if (active) openReports.value = true; }, { immediate: true });
</script>

<style scoped>
.sidebar-scroll::-webkit-scrollbar { width: 4px; }
.sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
.sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.2); border-radius: 4px; }
.sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(148,163,184,0.4); }
</style>
