<template>
  <div class="layout" :class="{ collapsed }">
    <aside class="sidebar" :class="{ collapsed }">
      <div class="brand-row">
        <img
          v-if="showLogo && companyLogoSrc"
          class="brand-logo"
          :src="companyLogoSrc"
          :alt="companyName"
          @error="onLogoError"
        />
        <div class="brand-text" style="padding-left: 10px">
          <h4 class="brand-title" :title="companyName">{{ companyName }}</h4>
          <div class="brand-subtitle">Payroll Module</div>
        </div>
        <button
          class="collapse-btn"
          @click="collapsed = !collapsed"
          :title="collapsed ? 'Expand' : 'Collapse'"
        >
          <ChevronRight :size="16" />
        </button>
      </div>
      <RouterLink
        v-if="navVisible(PAYROLL_ROUTE_ACCESS.payroll)"
        to="/"
        class="menu-item"
        :class="{ active: $route.path === '/' }"
      >
        <el-icon><LayoutDashboard /></el-icon>
        <span class="label">Payroll</span>
      </RouterLink>
      <nav class="menu">
        <div class="nav-section-label">Payroll</div>
        <template v-if="showTimeKeepingNav">
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-period'])"
            to="/payroll-period"
            class="menu-item"
          >
            <el-icon><CalendarClock /></el-icon>
            <span class="label">Payroll Period</span>
          </RouterLink>
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-item-schedule'])"
            to="/payroll-item-schedule"
            class="menu-item"
          >
            <el-icon><ClipboardList /></el-icon>
            <span class="label">Payroll Item Schedule</span>
          </RouterLink>
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['loan-application'])"
            to="/loan-application"
            class="menu-item"
          >
            <el-icon><FileSignature /></el-icon>
            <span class="label">Loan Application</span>
          </RouterLink>
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['income-deduction'])"
            to="/income-deduction"
            class="menu-item"
          >
            <el-icon><BanknoteArrowUp /></el-icon>
            <span class="label">Income and Deduction</span>
          </RouterLink>

          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['hdmf-premium'])"
            to="/hdmf-premium"
            class="menu-item"
          >
            <el-icon><ReceiptText /></el-icon>
            <span class="label">HDMF Premium</span>
          </RouterLink>
        </template>

        <template v-if="showPayrollCpmNav">
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-process'])"
            to="/payroll-process"
            class="menu-item"
          >
            <el-icon><Money /></el-icon>
            <span class="label">Payroll Process</span>
          </RouterLink>
          <RouterLink
            v-if="navVisible(PAYROLL_ROUTE_ACCESS['cos-payroll'])"
            to="/cos-payroll"
            class="menu-item"
          >
            <el-icon><Toolbox /></el-icon>
            <span class="label">COS Payroll</span>
          </RouterLink>
        </template>

        <RouterLink
          v-if="showBenefitsSection"
          to="/payroll-benefits"
          class="menu-item"
          :class="{ active: isBenefitsActive }"
        >
          <el-icon><Gift /></el-icon>
          <span class="label">Payroll Benefits</span>
        </RouterLink>
        <div class="nav-section-label" v-if="showReportsSection">Reports</div>
        <details v-if="showReportsSection" class="submenu" :open="true">
          <summary
            class="submenu-summary"
            :class="{ active: isReportsActive }"
            @click.prevent="toggleReports"
          >
            <div class="menu-item" role="button" aria-expanded="openReports">
              <el-icon><FileBarChart2 /></el-icon>
              <span class="label">Payroll Reports</span>
            </div>
            <span
              v-if="!collapsed"
              class="chevron"
              :class="{ open: openReports }"
              >▾</span
            >
          </summary>
          <el-collapse-transition>
            <div v-show="openReports" class="submenu-items">
              <RouterLink
                v-if="
                  navVisible(PAYROLL_ROUTE_ACCESS['payroll-summary-report'])
                "
                to="/payroll-summary-report"
                class="submenu-item"
                ><el-icon><FileBarChart2 /></el-icon
                ><span class="label">Payroll Summary reports</span></RouterLink
              >

              <!-- DISABLED AS PER REQUEST OF THE CLIENT ON THE  -->
              <!-- <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['payroll-summary-detailed-report'])"
                to="/payroll-summary-detailed-report"
                class="submenu-item"
                ><el-icon><FileBarChart2 /></el-icon
                ><span class="label"
                  >Payroll Summary with Detailed Deduction Report</span
                ></RouterLink
              > -->
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['payslip-report'])"
                to="/payslip-report"
                class="submenu-item"
                ><el-icon><Receipt /></el-icon
                ><span class="label">Payslip Report</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['loyalty-award-report'])"
                to="/loyalty-award-report"
                class="submenu-item"
                ><el-icon><Award /></el-icon
                ><span class="label">Loyalty Award Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(
                    PAYROLL_ROUTE_ACCESS['payroll-communication-macco-report'],
                  )
                "
                to="/payroll-communication-macco-report"
                class="submenu-item"
                ><el-icon><MessageSquare /></el-icon
                ><span class="label"
                  >Payroll Communication Macco Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(PAYROLL_ROUTE_ACCESS['bank-remittance-report'])
                "
                to="/bank-remittance-report"
                class="submenu-item"
                ><el-icon><Building2 /></el-icon
                ><span class="label">Bank Remittance Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(
                    PAYROLL_ROUTE_ACCESS['philhealth-remittance-report'],
                  )
                "
                to="/philhealth-remittance-report"
                class="submenu-item"
                ><el-icon><FileText /></el-icon
                ><span class="label"
                  >Philhealth Remittance Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(
                    PAYROLL_ROUTE_ACCESS['pag-ibig-contribution-report'],
                  )
                "
                to="/pag-ibig-contribution-report"
                class="submenu-item"
                ><el-icon><ReceiptText /></el-icon
                ><span class="label"
                  >Pag Ibig Contribution Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['pag-ibig-loan-report'])"
                to="/pag-ibig-loan-report"
                class="submenu-item"
                ><el-icon><FileSignature /></el-icon
                ><span class="label">Pag Ibig Loan Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(PAYROLL_ROUTE_ACCESS['gsis-remittance-report'])
                "
                to="/gsis-remittance-report"
                class="submenu-item"
                ><el-icon><FileText /></el-icon
                ><span class="label">GSIS Remittance Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(PAYROLL_ROUTE_ACCESS['overtime-payment-report'])
                "
                to="/overtime-payment-report"
                class="submenu-item"
                ><el-icon><Clock /></el-icon
                ><span class="label">Overtime Payment Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(
                    PAYROLL_ROUTE_ACCESS['uniform-clothing-allowance-report'],
                  )
                "
                to="/uniform-clothing-allowance-report"
                class="submenu-item"
                ><el-icon><Shirt /></el-icon
                ><span class="label"
                  >Uniform & Clothing Allowance Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['hazard-pay-report'])"
                to="/hazard-pay-report"
                class="submenu-item"
                ><el-icon><Shield /></el-icon
                ><span class="label"
                  >Hazard Pay Allowance Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['extra-bonus-report'])"
                to="/extra-bonus-report"
                class="submenu-item"
                ><el-icon><Gift /></el-icon
                ><span class="label"
                  >Extra Bonus Payroll Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['rata-payroll-report'])"
                to="/rata-payroll-report"
                class="submenu-item"
                ><el-icon><DollarSign /></el-icon
                ><span class="label">Rata Payroll Report</span></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(
                    PAYROLL_ROUTE_ACCESS['monetization-payroll-report'],
                  )
                "
                to="/monetization-payroll-report"
                class="submenu-item"
                ><el-icon><Wallet /></el-icon
                ><span class="label"
                  >Monetization Payroll Report</span
                ></RouterLink
              >
              <RouterLink
                v-if="
                  navVisible(PAYROLL_ROUTE_ACCESS['mid-year-bonus-report-hub'])
                "
                to="/mid-year-bonus-report-hub"
                class="submenu-item"
                ><el-icon><Gift /></el-icon
                ><span class="label">Mid Year Bonus Reports</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['year-end-bonus-report'])"
                to="/year-end-bonus-report"
                class="submenu-item"
                ><el-icon><Sparkles /></el-icon
                ><span class="label">Year end Bonus Report</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['subsistence-report'])"
                to="/subsistence-report"
                class="submenu-item"
                ><el-icon><FileText /></el-icon
                ><span class="label">SUBSISTENCE Report</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['landbank-text-report'])"
                to="/landbank-text-report"
                class="submenu-item"
                ><el-icon><FileType /></el-icon
                ><span class="label">Landbank Text Report</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['atm-letter-landbank'])"
                to="/atm-letter-landbank"
                class="submenu-item"
                ><el-icon><FileText /></el-icon
                ><span class="label">ATM Letter for Landbank</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['bir-form-2305'])"
                to="/bir-form-2305"
                class="submenu-item"
                ><el-icon><FileCheck /></el-icon
                ><span class="label">BIR Form 2305</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['gsis-member-info'])"
                to="/gsis-member-info"
                class="submenu-item"
                ><el-icon><UserCircle /></el-icon
                ><span class="label"
                  >GSIS Member Information Sheet</span
                ></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['pagibig-mdf'])"
                to="/pag-ibig-mdf"
                class="submenu-item"
                ><el-icon><FileSpreadsheet /></el-icon
                ><span class="label">Pag-IBIG MDF</span></RouterLink
              >
              <RouterLink
                v-if="navVisible(PAYROLL_ROUTE_ACCESS['philhealth-pmrf'])"
                to="/philhealth-pmrf"
                class="submenu-item"
                ><el-icon><FileSpreadsheet /></el-icon
                ><span class="label">PhilHealth PMRF</span></RouterLink
              >
            </div>
          </el-collapse-transition>
        </details>
      </nav>
      <div class="user-section">
        <div class="user-separator"></div>
        <div class="user-info">
          <div class="user-avatar">
            <span v-if="!collapsed">{{ userInitials }}</span>
            <el-icon v-else><User /></el-icon>
          </div>
          <div v-if="!collapsed" class="user-details">
            <div class="user-name">{{ userDisplayName }}</div>
            <div v-if="userDisplayEmail" class="user-email">
              {{ userDisplayEmail }}
            </div>
          </div>
        </div>
        <button
          class="sidebar-logout-btn"
          @click="handleLogout"
          :disabled="loading"
        >
          <el-icon><SwitchButton /></el-icon>
          <span v-if="!collapsed">Logout</span>
        </button>
      </div>
    </aside>
    <section class="content">
      <header class="content-header">
        <div class="header-content">
          <slot name="header">Payroll Module</slot>
        </div>
      </header>
      <main>
        <slot />
      </main>
    </section>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { ElIcon, ElCollapseTransition } from "element-plus";
import { User, SwitchButton, Money } from "@element-plus/icons-vue";
import {
  LayoutDashboard,
  CalendarClock,
  ClipboardList,
  PiggyBank,
  FileSignature,
  Gift,
  FileBarChart2,
  Clock,
  Shirt,
  MessageSquare,
  Award,
  DollarSign,
  Shield,
  Wallet,
  Sparkles,
  FileText,
  Receipt,
  Building2,
  FileSpreadsheet,
  FileCheck,
  FileType,
  UserCircle,
  ReceiptText,
  Landmark,
  BanknoteArrowUp,
  Toolbox,
  ChevronRight,
} from "lucide-vue-next";
import { useAuth, ensurePayrollAccessUser } from "@/Composables/useAuth";
import { useCompany } from "@/Composables/useCompany.js";
import {
  PAYROLL_ROUTE_ACCESS,
  payrollNavVisible,
  TIME_KEEPING_NAV_NAMES,
  PAYROLL_CPM_NAV_NAMES,
  BENEFIT_ROUTE_NAMES,
  REPORT_ROUTE_NAMES,
  showAnyNamedRoutes,
  canSeeBenefitsSection,
} from "@/config/payrollAccess";

const userInitials = computed(() => {
  if (user.value?.name) {
    return user.value.name
      .split(" ")
      .filter(Boolean)
      .map((w) => w[0].toUpperCase())
      .slice(0, 2)
      .join("");
  }
  if (user.value?.email) {
    return user.value.email[0].toUpperCase();
  }
  return "?";
});

const route = useRoute();
const { logout, loading, user, getCurrentUser, checkAuth } = useAuth();
const { primaryCompany, fetchCompanies, getLogoUrl } = useCompany();

const showLogo = ref(true);

const companyName = computed(() => primaryCompany.value?.name || "Payroll Module");
const companyLogoSrc = computed(() => getLogoUrl(primaryCompany.value));

function onLogoError() {
  showLogo.value = false;
}

watch(primaryCompany, () => {
  showLogo.value = true;
});

const navVisible = (spec) => payrollNavVisible(user.value, spec);

const showTimeKeepingNav = computed(() =>
  showAnyNamedRoutes(user.value, TIME_KEEPING_NAV_NAMES),
);
const showPayrollCpmNav = computed(() =>
  showAnyNamedRoutes(user.value, PAYROLL_CPM_NAV_NAMES),
);
const showBenefitsSection = computed(() => canSeeBenefitsSection(user.value));
const REPORT_PARENT_KEYS = [
  "payroll_reports",
  "payroll_report",
  "payroll-reports",
];
const showReportsSection = computed(() =>
  navVisible({ keys: REPORT_PARENT_KEYS }),
);
const openReports = ref(false);
const openBenefits = ref(false);
const collapsed = ref(false);

// Computed user display name
const userDisplayName = computed(() => {
  if (user.value?.name) return user.value.name;
  if (user.value?.email) return user.value.email.split("@")[0];
  return "User";
});

// Computed user email
const userDisplayEmail = computed(() => {
  return user.value?.email || "";
});

onMounted(async () => {
  if (!checkAuth()) return;
  try {
    await ensurePayrollAccessUser();
    if (!user.value?.email) {
      await getCurrentUser();
    }
    await fetchCompanies();
  } catch (error) {
    console.error("Failed to fetch user data:", error);
  }
});
const isReportsActive = computed(() =>
  route.name ? REPORT_ROUTE_NAMES.includes(route.name) : false,
);
const isBenefitsActive = computed(() =>
  route.path.startsWith("/payroll-benefits"),
);
function toggleReports() {
  openReports.value = !openReports.value;
}
function toggleBenefits() {
  openBenefits.value = !openBenefits.value;
}

// Logout handler
const handleLogout = async () => {
  await logout();
  // Dispatch event to trigger login modal in App.vue
  window.dispatchEvent(new CustomEvent("user-logout"));
};

// Auto-open submenu when navigating to a child route, but don't force-close on navigate away
watch(
  isReportsActive,
  (active) => {
    if (active) openReports.value = true;
  },
  { immediate: true },
);
watch(
  isBenefitsActive,
  (active) => {
    if (active) openBenefits.value = true;
  },
  { immediate: true },
);
</script>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  min-height: 100vh;
  transition: grid-template-columns 0.25s ease;
}
.layout.collapsed {
  grid-template-columns: 76px 1fr;
}
.sidebar {
  background: #ffffff;
  border-right: 1px dashed #e6e6e6;
  padding: 1.25rem;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  overflow-x: visible;
  scrollbar-gutter: stable both-edges;
  display: flex;
  flex-direction: column;
  transition: padding 0.25s ease;
}
.sidebar.collapsed {
  padding: 1rem 0.5rem;
  align-items: center;
}
.brand {
  margin: 0 0 1rem 0;
}
.brand-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  transition: gap 0.25s ease;
}
.sidebar.collapsed .brand-row {
  grid-template-columns: auto;
  justify-items: center;
  gap: 6px;
}
.brand-logo {
  height: 40px;
  width: 40px;
  min-width: 40px;
  flex-shrink: 0;
  border-radius: 10px;
  object-fit: contain;
}
.brand-text {
  line-height: 1.1;
  min-width: 0;
  overflow: hidden;
}
.brand-title {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.brand-subtitle {
  color: #94a3b8;
  font-size: 11px;
  white-space: nowrap;
}
.collapse-btn {
  border: 1px solid #e5e7eb;
  background: #fff;
  border-radius: 8px;
  cursor: pointer;
  padding: 2px 6px;
  transition: transform 0.2s ease;
  color: #000;
}
.sidebar.collapsed .collapse-btn {
  transform: rotate(180deg);
}
.menu {
  display: grid;
  gap: 0.25rem;
}
.menu-item {
  color: #334155;
  text-decoration: none;
  padding: 6px 8px;
  border-radius: 8px;
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: center;
  column-gap: 8px;
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition:
    background 0.2s ease,
    color 0.2s ease;
}
.menu-item.router-link-active {
  background: #eef2f7;
  font-weight: 600;
}
.submenu {
  margin-top: 4px;
}
.menu-item:hover {
  background: #f8fafc;
}
.submenu summary {
  list-style: none;
}
.submenu-summary {
  list-style: none;
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
}
.submenu-summary.active {
  background: #eef2f7;
  border-radius: 8px;
  padding: 4px 6px;
}
.submenu {
  contain: layout paint;
}
.chevron {
  color: #94a3b8;
  transition: transform 0.15s ease;
}
.chevron.open {
  transform: rotate(180deg);
}
.submenu-summary .menu-item {
  display: grid;
  grid-auto-flow: column;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}
.submenu-items {
  display: grid;
  gap: 2px;
  margin-left: 10px;
  padding-left: 12px;
  border-left: 1px dashed #e6e6e6;
}
.submenu-item {
  color: #64748b;
  text-decoration: none;
  padding: 5px 8px;
  border-radius: 6px;
  display: grid;
  grid-template-columns: 20px 1fr;
  align-items: center;
  column-gap: 8px;
  font-size: 12px;
  white-space: normal;
  overflow: hidden;
  text-overflow: ellipsis;
}
.submenu-item.router-link-active {
  background: #eef2f7;
}
.content {
  background: #f8fafc;
  height: 100vh;
  overflow: auto;
  scrollbar-gutter: stable both-edges;
}
.content-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px dashed #e6e6e6;
  background: #ffffff;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
main {
  padding: 1.5rem;
}
.layout.collapsed .label,
.layout.collapsed .brand-text,
.layout.collapsed .brand-subtitle,
.layout.collapsed .user-details,
.layout.collapsed .submenu-items {
  display: none;
}
.layout.collapsed .menu-item,
.layout.collapsed .submenu-summary .menu-item,
.layout.collapsed .submenu-item {
  grid-template-columns: 1fr;
  justify-items: center;
  padding: 0.5rem;
}
.layout.collapsed .menu-item .el-icon,
.layout.collapsed .submenu-item .el-icon {
  margin: 0 auto;
}
.layout.collapsed .submenu-summary {
  grid-template-columns: 1fr;
  justify-items: center;
}
.layout.collapsed .submenu {
  position: relative;
  width: 100%;
}
.layout.collapsed .submenu-items {
  position: absolute;
  left: calc(100% + 8px);
  top: 0;
  display: none;
  padding: 0.75rem;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
  min-width: 230px;
  z-index: 10;
}
.layout.collapsed .submenu:hover .submenu-items,
.layout.collapsed .submenu:focus-within .submenu-items {
  display: grid;
}
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
.user-section {
  margin-top: auto;
  padding-top: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  width: 100%;
}
.user-separator {
  border-top: 1px dashed #e6e6e6;
  margin: 0 -1.25rem;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #eef2f7;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #1e3a5f;
  font-size: 12px;
  font-weight: 600;
  flex-shrink: 0;
}
.user-avatar .el-icon {
  font-size: 18px;
}
.user-details {
  flex: 1;
  min-width: 0;
}
.user-name {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
  line-height: 1.4;
}
.user-email {
  color: #64748b;
  font-size: 12px;
  line-height: 1.4;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sidebar-logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  padding: 8px 16px;
  background: #ffffff;
  color: #dc2626;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  font-size: 14px;
  transition: all 0.2s;
}
.sidebar-logout-btn:hover:not(:disabled) {
  background: #fef2f2;
  border-color: #dc2626;
}
.sidebar-logout-btn:disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
  border-color: #e5e7eb;
}
.sidebar-logout-btn .el-icon {
  color: #dc2626;
  font-size: 16px;
}
.sidebar-logout-btn:disabled .el-icon {
  color: #9ca3af;
}
.layout.collapsed .user-info {
  justify-content: center;
  width: auto;
  margin: 0 auto;
}
.layout.collapsed .user-avatar {
  margin: 0 auto;
}
.layout.collapsed .sidebar-logout-btn {
  width: 40px;
  height: 40px;
  padding: 0;
  min-width: 0;
  border-radius: 999px;
  border-color: transparent;
  box-shadow: 0 0 0 1px #e5e7eb;
  align-self: center;
}
.layout.collapsed .sidebar-logout-btn span {
  display: none;
}
.layout.collapsed .user-section {
  align-items: center;
}
.nav-section-label {
  font-size: 10px;
  font-weight: 500;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 10px 8px 4px;
}

.layout.collapsed .nav-section-label {
  display: none;
}
</style>
