<template>
  <div class="payroll-dashboard">
    <!-- Header -->
    <div class="dash-header">
      <div>
        <h2 class="dash-title">Payroll Management</h2>
        <p class="dash-sub">Private Sector · Cut-off to Disbursement Workflow</p>
      </div>
      <input v-model="search" class="dash-search" placeholder="Search modules…" />
    </div>

    <!-- Core Setup -->
    <div v-if="coreSetup.length" class="section">
      <div class="section-label">
        <span class="section-dot setup"></span> Core Setup
      </div>
      <div class="grid">
        <div
          v-for="mod in coreSetup"
          :key="mod.path"
          class="card"
          @click="$router.push(mod.path)"
        >
          <div class="card-icon" :class="mod.color">
            <component :is="mod.icon" class="icon-svg" />
          </div>
          <div class="card-body">
            <p class="card-title">{{ mod.title }}</p>
            <p class="card-desc">{{ mod.description }}</p>
          </div>
          <div class="card-arrow">→</div>
        </div>
      </div>
    </div>

    <!-- Payroll Execution -->
    <div v-if="execution.length" class="section">
      <div class="section-label">
        <span class="section-dot exec"></span> Payroll Execution
      </div>
      <div class="grid">
        <div
          v-for="mod in execution"
          :key="mod.path"
          class="card card-accent"
          @click="$router.push(mod.path)"
        >
          <div class="card-icon" :class="mod.color">
            <component :is="mod.icon" class="icon-svg" />
          </div>
          <div class="card-body">
            <p class="card-title">{{ mod.title }}</p>
            <p class="card-desc">{{ mod.description }}</p>
          </div>
          <div class="card-arrow">→</div>
        </div>
      </div>
    </div>

    <!-- Bonuses & Overtime -->
    <div v-if="bonuses.length" class="section">
      <div class="section-label">
        <span class="section-dot bonus"></span> Bonuses & Overtime
      </div>
      <div class="grid">
        <div
          v-for="mod in bonuses"
          :key="mod.path"
          class="card"
          @click="$router.push(mod.path)"
        >
          <div class="card-icon" :class="mod.color">
            <component :is="mod.icon" class="icon-svg" />
          </div>
          <div class="card-body">
            <p class="card-title">{{ mod.title }}</p>
            <p class="card-desc">{{ mod.description }}</p>
          </div>
          <div class="card-arrow">→</div>
        </div>
      </div>
    </div>

    <div v-if="!allModules.length" class="empty-state">
      <p>No modules found for "{{ search }}".</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import {
  CalendarClock, ClipboardList, BanknoteArrowUp, FileSignature,
  ReceiptText, DollarSign, CalendarDays, CircleCheckBig, Clock, Gift, Sparkles,
} from "lucide-vue-next";
import {
  PAYROLL_ROUTE_ACCESS,
  payrollNavVisible,
} from "@/config/payrollAccess";
import { useAuth, ensurePayrollAccessUser } from "@/Composables/useAuth";

const search = ref("");
const { user } = useAuth();
const visible = (name) => payrollNavVisible(user.value, PAYROLL_ROUTE_ACCESS[name]);

const MODULES = {
  coreSetup: [
    {
      name: "payroll-period", title: "Payroll Period", path: "/payroll-period",
      icon: CalendarClock, color: "ic-blue",
      description: "Configure cut-off periods and payroll calendar.",
    },
    {
      name: "payroll-item-schedule", title: "Item Schedule", path: "/payroll-item-schedule",
      icon: ClipboardList, color: "ic-indigo",
      description: "Define earnings, deductions, and contribution types.",
    },
    {
      name: "income-deduction", title: "Income & Deduction", path: "/income-deduction",
      icon: BanknoteArrowUp, color: "ic-teal",
      description: "Per-employee income and deduction adjustments.",
    },
    {
      name: "hdmf-premium", title: "Pag-IBIG Premium", path: "/hdmf-premium",
      icon: ReceiptText, color: "ic-amber",
      description: "HDMF contribution tracking per payroll period.",
    },
    {
      name: "loan-application", title: "Loan Management", path: "/loan-application",
      icon: FileSignature, color: "ic-rose",
      description: "Loans, cash advances, amortization & balance tracking.",
    },
  ],
  execution: [
    {
      name: "payroll-process", title: "Payroll Process", path: "/payroll-process",
      icon: DollarSign, color: "ic-green",
      description: "Compute gross pay, statutory deductions, and net pay.",
    },
    {
      name: "thirteenth-month-pay", title: "13th Month Pay", path: "/thirteenth-month-pay",
      icon: CalendarDays, color: "ic-purple",
      description: "Compute and release mandated 13th month pay.",
    },
    {
      name: "final-pay", title: "Final Pay", path: "/final-pay",
      icon: CircleCheckBig, color: "ic-slate",
      description: "Final pay computation linked to offboarding clearance.",
    },
  ],
  bonuses: [
    {
      name: "overtime-payment", title: "Overtime Payment", path: "/payroll-bonuses/overtime-payment",
      icon: Clock, color: "ic-orange",
      description: "Process and disburse approved overtime pay.",
    },
    {
      name: "extra-bonus", title: "Other Bonuses", path: "/payroll-bonuses/extra-bonus",
      icon: Sparkles, color: "ic-pink",
      description: "Company-defined bonus types (performance, special, etc.).",
    },
  ],
};

const filter = (list) => {
  const q = search.value.trim().toLowerCase();
  return list
    .filter((m) => visible(m.name))
    .filter((m) => !q || m.title.toLowerCase().includes(q) || m.description.toLowerCase().includes(q));
};

const coreSetup = computed(() => filter(MODULES.coreSetup));
const execution = computed(() => filter(MODULES.execution));
const bonuses  = computed(() => filter(MODULES.bonuses));
const allModules = computed(() => [...coreSetup.value, ...execution.value, ...bonuses.value]);

onMounted(() => ensurePayrollAccessUser());
</script>

<style scoped>
.payroll-dashboard { width: 100%; }

.dash-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}
.dash-title { font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; }
.dash-sub   { font-size: 13px; color: #64748b; margin: 4px 0 0; }
.dash-search {
  width: 260px; padding: 9px 16px;
  border: 1px solid #e2e8f0; border-radius: 12px;
  font-size: 13px; color: #374151; background: #f8fafc;
  outline: none; transition: border-color 0.2s;
}
.dash-search:focus { border-color: #3b5eff; background: #fff; }

.section { margin-bottom: 32px; }
.section-label {
  display: flex; align-items: center; gap: 8px;
  font-size: 11px; font-weight: 700; letter-spacing: 0.08em;
  text-transform: uppercase; color: #94a3b8; margin-bottom: 14px;
}
.section-dot { width: 8px; height: 8px; border-radius: 50%; }
.section-dot.setup { background: #3b5eff; }
.section-dot.exec  { background: #10b981; }
.section-dot.bonus { background: #f59e0b; }

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
}

.card {
  display: flex; align-items: center; gap: 16px;
  padding: 18px 20px;
  background: #fff;
  border: 1px solid #e8edf5;
  border-radius: 14px;
  cursor: pointer;
  transition: box-shadow 0.18s, transform 0.18s, border-color 0.18s;
}
.card:hover {
  box-shadow: 0 8px 24px rgba(59,94,255,0.1);
  transform: translateY(-2px);
  border-color: #c7d2fe;
}
.card-accent { border-left: 3px solid #3b5eff; }

.card-icon {
  flex-shrink: 0;
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
}
.icon-svg { width: 20px; height: 20px; }

/* Icon color variants */
.ic-blue   { background: #eff6ff; color: #3b82f6; }
.ic-indigo { background: #eef2ff; color: #6366f1; }
.ic-teal   { background: #f0fdfa; color: #0d9488; }
.ic-amber  { background: #fffbeb; color: #d97706; }
.ic-rose   { background: #fff1f2; color: #e11d48; }
.ic-green  { background: #f0fdf4; color: #16a34a; }
.ic-purple { background: #faf5ff; color: #9333ea; }
.ic-slate  { background: #f8fafc; color: #475569; }
.ic-orange { background: #fff7ed; color: #ea580c; }
.ic-pink   { background: #fdf2f8; color: #db2777; }

.card-body { flex: 1; min-width: 0; }
.card-title { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
.card-desc  { font-size: 12px; color: #64748b; margin: 0; line-height: 1.4; }

.card-arrow {
  font-size: 18px; color: #cbd5e1;
  transition: color 0.15s, transform 0.15s;
  flex-shrink: 0;
}
.card:hover .card-arrow { color: #3b5eff; transform: translateX(4px); }

.empty-state {
  text-align: center; padding: 60px 0;
  color: #94a3b8; font-size: 14px;
}
</style>
