<template>
  <PageScaffold
    title="Payroll Period"
    subtitle="Configure and manage payroll periods and cut-off dates"
    :breadcrumbs="[
      { label: 'Payroll Module', to: '/' },
      { label: 'Payroll Period' },
    ]"
  >
    <template #actions>
      <!-- <button class="btn-ghost" @click="showCreateMonthDialog = true">
        Create month
      </button> -->
      <button class="btn-primary" @click="onCreate">
        <svg width="13" height="13" viewBox="0 0 13 13" fill="none">
          <path
            d="M6.5 2v9M2 6.5h9"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        Create period
      </button>
    </template>

    <!-- Tabs -->
    <div class="tab-bar">
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'regular' }"
        @click="activeTab = 'regular'"
      >
        Regular payroll
      </button>
      <button
        class="tab-btn"
        :class="{ active: activeTab === 'cos' }"
        @click="activeTab = 'cos'"
      >
        COS payroll
      </button>
    </div>

    <div v-show="activeTab === 'regular'">
      <Payroll_PeriodTable
        :rows="regularPayrollRows"
        :flat-row-count="regularPayrollRowsFlat.length"
        :loading="loading"
        @view="onViewGroup"
        @edit="onEdit"
        @deleted="reload"
        @refresh="reload"
      />
    </div>
    <div v-show="activeTab === 'cos'">
      <Payroll_PeriodTable
        :rows="cosPayrollRows"
        :flat-row-count="cosPayrollRowsFlat.length"
        :loading="loading"
        @view="onViewGroup"
        @edit="onEdit"
        @deleted="reload"
        @refresh="reload"
      />
    </div>

    <Payroll_PeriodForm v-model="showForm" :edit-id="editId" @saved="reload" />

    <!-- Create month dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showCreateMonthDialog"
          class="modal-backdrop"
          @click.self="showCreateMonthDialog = false"
        >
          <div class="modal-card">
            <div class="modal-header">
              <div>
                <h3 class="modal-title">Create month (1st &amp; 2nd half)</h3>
                <p class="modal-sub">
                  Creates two periods: 1st half (full previous calendar month,
                  release 15th) and 2nd half (full selected month, release last
                  day).
                </p>
              </div>
              <button class="close-btn" @click="showCreateMonthDialog = false">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path
                    d="M2 2l10 10M12 2L2 12"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="field-grid two-col">
                <div class="field">
                  <label class="field-label">Year</label>
                  <select v-model="createMonthYear" class="field-input">
                    <option v-for="y in yearOptions" :key="y" :value="y">
                      {{ y }}
                    </option>
                  </select>
                </div>
                <div class="field">
                  <label class="field-label">Month</label>
                  <select v-model="createMonthMonth" class="field-input">
                    <option v-for="m in 12" :key="m" :value="m">
                      {{ monthName(m) }}
                    </option>
                  </select>
                </div>
              </div>

              <div class="field">
                <label class="field-label"
                  >Employment types <span class="req">*</span></label
                >
                <div v-if="createMonthLoadingEtypes" class="loading-text">
                  Loading…
                </div>
                <div
                  v-else-if="!createMonthEmploymentTypes.length"
                  class="muted-text"
                >
                  No active employment types found.
                </div>
                <div v-else class="checkbox-group">
                  <label
                    v-for="et in createMonthEmploymentTypes"
                    :key="et.id"
                    class="check-chip"
                    :class="{
                      checked: createMonthEmploymentTypeIds.includes(et.id),
                    }"
                  >
                    <input
                      type="checkbox"
                      :value="et.id"
                      v-model="createMonthEmploymentTypeIds"
                      class="check-hidden"
                    />
                    {{ et.name }}
                  </label>
                </div>
              </div>

              <div class="field-grid two-col">
                <div class="field">
                  <label class="field-label">1st half release date</label>
                  <input
                    type="date"
                    v-model="createMonthFirstHalfReleaseDate"
                    class="field-input"
                  />
                </div>
                <div class="field">
                  <label class="field-label">2nd half release date</label>
                  <input
                    type="date"
                    v-model="createMonthSecondHalfReleaseDate"
                    class="field-input"
                  />
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-ghost" @click="showCreateMonthDialog = false">
                Cancel
              </button>
              <button
                class="btn-primary"
                :disabled="createMonthLoading"
                @click="onCreateMonth"
              >
                <svg
                  v-if="createMonthLoading"
                  class="spin-icon"
                  width="13"
                  height="13"
                  viewBox="0 0 13 13"
                  fill="none"
                >
                  <circle
                    cx="6.5"
                    cy="6.5"
                    r="5"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-dasharray="20"
                    stroke-dashoffset="10"
                    stroke-linecap="round"
                  />
                </svg>
                {{ createMonthLoading ? "Creating…" : "Create" }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- View group dialog -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showViewDialog"
          class="modal-backdrop"
          @click.self="showViewDialog = false"
        >
          <div class="modal-card modal-card-sm">
            <div class="modal-header">
              <h3 class="modal-title">{{ viewGroupTitle }}</h3>
              <button class="close-btn" @click="showViewDialog = false">
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                  <path
                    d="M2 2l10 10M12 2L2 12"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                  />
                </svg>
              </button>
            </div>
            <div class="modal-body">
              <div v-if="selectedGroup" class="period-list">
                <div
                  v-for="period in sortedPeriodsInGroup"
                  :key="period.id"
                  class="period-row"
                >
                  <div class="period-info">
                    <span class="period-name">{{
                      getPeriodDisplayName(period)
                    }}</span>
                    <span class="period-dates">{{
                      formatPeriodDates(period)
                    }}</span>
                    <span
                      class="status-pill"
                      :class="
                        period.posted === 'YES' || period.posted === true
                          ? 'posted'
                          : 'unposted'
                      "
                    >
                      {{
                        period.posted === "YES" || period.posted === true
                          ? "Posted"
                          : "Unposted"
                      }}
                    </span>
                  </div>
                  <button
                    class="btn-edit-sm"
                    @click="onEditPeriodFromView(period)"
                  >
                    Edit
                  </button>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn-ghost" @click="showViewDialog = false">
                Close
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import PageScaffold from "../../components/PageScaffold.vue";
import Payroll_PeriodTable from "../../components/Payroll_Period/Payroll_PeriodTable.vue";
import Payroll_PeriodForm from "../../components/Payroll_Period/Payroll_PeriodForm.vue";
import api from "../../services/api";

const rows = ref([]);
const loading = ref(false);
const showForm = ref(false);
const editId = ref(0);
const activeTab = ref("regular");
const showViewDialog = ref(false);
const selectedGroup = ref(null);

const showCreateMonthDialog = ref(false);
const createMonthYear = ref(new Date().getFullYear());
const createMonthMonth = ref(new Date().getMonth() + 1);
const createMonthEmploymentTypeIds = ref([]);
const createMonthEmploymentTypes = ref([]);
const createMonthLoadingEtypes = ref(false);
const createMonthLoading = ref(false);
const createMonthFirstHalfReleaseDate = ref(null);
const createMonthSecondHalfReleaseDate = ref(null);
const yearOptions = Array.from(
  { length: 11 },
  (_, i) => new Date().getFullYear() - 2 + i,
);

function monthName(m) {
  return new Date(2000, m - 1, 1).toLocaleString("en-US", { month: "long" });
}

const loadEmploymentTypesForCreateMonth = async () => {
  createMonthLoadingEtypes.value = true;
  createMonthEmploymentTypeIds.value = [];
  try {
    const res = await api.get("/employment-types");
    const data = res.data?.data ?? res.data ?? [];
    createMonthEmploymentTypes.value = (Array.isArray(data) ? data : []).filter(
      (et) => {
        if (!et?.id) return false;
        const a = et.active;
        return (
          a === true ||
          a === 1 ||
          a === "1" ||
          String(a || "").toLowerCase() === "true"
        );
      },
    );
  } catch {
    createMonthEmploymentTypes.value = [];
  } finally {
    createMonthLoadingEtypes.value = false;
  }
};

watch(showCreateMonthDialog, (v) => {
  if (v) {
    loadEmploymentTypesForCreateMonth();
    createMonthFirstHalfReleaseDate.value = null;
    createMonthSecondHalfReleaseDate.value = null;
  }
});
watch(showCreateMonthDialog, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow = v || showViewDialog.value ? "hidden" : "";
});
watch(showViewDialog, (v) => {
  if (typeof document !== "undefined")
    document.body.style.overflow =
      v || showCreateMonthDialog.value ? "hidden" : "";
});

const isCOSPayroll = (p) => {
  if (!p.employment_types || !Array.isArray(p.employment_types)) return false;
  const kw = ["contract of service", "service provider", "consultant", "cos"];
  return p.employment_types.some((et) =>
    kw.some((k) =>
      (typeof et === "string" ? et : et?.name || "")
        .toLowerCase()
        .trim()
        .includes(k),
    ),
  );
};
const isRegularPayroll = (p) => !isCOSPayroll(p);

const regularPayrollRowsFlat = computed(() =>
  (rows.value || []).filter(isRegularPayroll),
);
const cosPayrollRowsFlat = computed(() =>
  (rows.value || []).filter(isCOSPayroll),
);
const regularPayrollRows = computed(() => regularPayrollRowsFlat.value);
const cosPayrollRows = computed(() => cosPayrollRowsFlat.value);

const reload = async () => {
  loading.value = true;
  try {
    const { data } = await api.get("/payroll-periods");
    rows.value = data.data || [];
  } finally {
    loading.value = false;
  }
};
const onCreate = () => {
  editId.value = 0;
  showForm.value = true;
};
const onCreateMonth = async () => {
  if (!createMonthEmploymentTypeIds.value?.length) return;
  createMonthLoading.value = true;
  try {
    const { data } = await api.post("/payroll-periods/create-monthly", {
      year: createMonthYear.value,
      month: createMonthMonth.value,
      employment_type_ids: createMonthEmploymentTypeIds.value,
      first_half_release_date: createMonthFirstHalfReleaseDate.value,
      second_half_release_date: createMonthSecondHalfReleaseDate.value,
    });
    showCreateMonthDialog.value = false;
    await reload();
  } catch (e) {
    console.error(e);
  } finally {
    createMonthLoading.value = false;
  }
};
const onEdit = (row) => {
  editId.value = Number(row.id);
  showForm.value = true;
};
const viewGroupTitle = computed(() =>
  selectedGroup.value
    ? `Payroll period — ${selectedGroup.value.label}`
    : "Payroll period",
);
const sortedPeriodsInGroup = computed(() => {
  if (!selectedGroup.value?.periods?.length) return [];
  return [...selectedGroup.value.periods].sort((a, b) =>
    (a.attendance_start_date || "").localeCompare(
      b.attendance_start_date || "",
    ),
  );
});
function formatPeriodDates(period) {
  const fmt = (s) =>
    s
      ? new Date(s).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
        })
      : "";
  return [fmt(period.attendance_start_date), fmt(period.attendance_end_date)]
    .filter(Boolean)
    .join(" – ");
}
function getPeriodDisplayName(period) {
  const label = selectedGroup.value?.label || "";
  if (String(label).toLowerCase().includes("monthly")) return "Monthly";
  return period.payroll_cutoff || "Period";
}
const onViewGroup = (group) => {
  selectedGroup.value = group;
  showViewDialog.value = true;
};
const onEditPeriodFromView = (period) => {
  showViewDialog.value = false;
  onEdit(period);
};

onMounted(() => {
  reload();
});
</script>

<style scoped>
.tab-bar {
  display: flex;
  gap: 0;
  border-bottom: 2px solid #f3f4f6;
  margin-bottom: 20px;
}
.tab-btn {
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  padding: 10px 20px;
  font-size: 13px;
  font-weight: 500;
  color: #9ca3af;
  cursor: pointer;
  transition:
    color 0.15s,
    border-color 0.15s;
}
.tab-btn.active {
  color: #111827;
  border-bottom-color: #409eff;
}
.tab-btn:hover:not(.active) {
  color: #374151;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:hover:not(:disabled) {
  opacity: 0.88;
}
.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-ghost {
  background: transparent;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
}
.btn-ghost:hover {
  background: #f3f4f6;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.modal-card {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.14);
}
.modal-card-sm {
  max-width: 480px;
}
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.modal-title {
  font-size: 16px;
  font-weight: 500;
  color: #111827;
  margin: 0 0 3px;
}
.modal-sub {
  font-size: 12px;
  color: #9ca3af;
  margin: 0;
  line-height: 1.5;
}
.modal-body {
  padding: 18px 24px;
  overflow-y: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 24px;
  border-top: 1px solid #f3f4f6;
  flex-shrink: 0;
}
.close-btn {
  background: transparent;
  border: none;
  border-radius: 7px;
  padding: 5px;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  flex-shrink: 0;
}
.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

/* Fields */
.field-grid {
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
}
.field-grid.two-col > .field {
  flex: 1;
  min-width: 0;
}
.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.field-label {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
}
.req {
  color: #a32d2d;
}
.field-input {
  font-size: 13px;
  padding: 8px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #fff;
  color: #111827;
  outline: none;
  width: 100%;
}
.field-input:focus {
  border-color: #6b7280;
}

.checkbox-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.check-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  font-size: 13px;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.12s;
}
.check-chip.checked {
  background: #e1f5ee;
  border-color: #9fe1cb;
  color: #085041;
  font-weight: 500;
}
.check-hidden {
  display: none;
}
.loading-text {
  font-size: 13px;
  color: #9ca3af;
}
.muted-text {
  font-size: 12px;
  color: #9ca3af;
}

/* Period view list */
.period-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.period-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #f9fafb;
}
.period-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.period-name {
  font-weight: 500;
  font-size: 13px;
  color: #111827;
}
.period-dates {
  font-size: 12px;
  color: #9ca3af;
}
.status-pill {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  width: fit-content;
}
.status-pill.posted {
  background: #e1f5ee;
  color: #085041;
}
.status-pill.unposted {
  background: #faece7;
  color: #712b13;
}
.btn-edit-sm {
  background: #409eff;
  color: #fff;
  border: none;
  border-radius: 7px;
  padding: 5px 14px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.btn-edit-sm:hover {
  opacity: 0.88;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.spin-icon {
  animation: spin 0.8s linear infinite;
}
</style>
