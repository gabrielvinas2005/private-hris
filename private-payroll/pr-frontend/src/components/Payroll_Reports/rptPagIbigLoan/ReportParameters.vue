<template>
  <div class="container">
    <el-form label-width="140px" class="centered-form">
      <el-row :gutter="16" justify="center">
        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Report Information</div>

            <el-form-item label="Payroll Period" required>
              <el-select
                v-model="localForm.payroll_period_id"
                placeholder="Select Period"
                required
              >
                <el-option
                  v-for="p in displayPayrollPeriods"
                  :key="p.id"
                  :label="getPeriodLabel(p)"
                  :value="p.id"
                >
                  <div class="period-option-row">
                    <span class="period-option-label">{{ getPeriodLabel(p) }}</span>
                    <span class="cutoff-tags-inline">
                      <PayrollCutoffTag
                        v-for="name in p.cutoff_names || []"
                        :key="name"
                        :name="name"
                      />
                    </span>
                  </div>
                </el-option>
              </el-select>
            </el-form-item>

            <el-form-item label="Loan Type" required>
              <el-select
                v-model="localForm.membership_program"
                placeholder="Select Loan Type"
                required
              >
                <el-option
                  v-for="p in membershipPrograms"
                  :key="p.id"
                  :label="p.name"
                  :value="p.id"
                />
              </el-select>
            </el-form-item>

            <!-- Loan report does not require selecting a loan type -->
          </el-card>
        </el-col>
      </el-row>
    </el-form>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  formData: { type: Object, required: true },
  signatories: { type: Object, required: true },
  payrollPeriods: { type: Array, default: () => [] },
  membershipPrograms: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:formData", "update:signatories"]);

const localForm = reactive({ ...props.formData });
const localSignatories = reactive({ ...props.signatories });

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

// One dropdown row per month; both cutoffs shown as tags on the same line
const displayPayrollPeriods = computed(() => {
  const periods = Array.isArray(props.payrollPeriods) ? props.payrollPeriods : [];
  const byMonth = new Map();

  for (const p of periods) {
    const d = p?.release_date ? new Date(p.release_date) : null;
    if (!d || Number.isNaN(d.getTime())) continue;

    const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    const key = `${p.payroll_interval_id ?? ""}|${ym}`;
    if (!byMonth.has(key)) byMonth.set(key, []);
    byMonth.get(key).push(p);
  }

  const out = [];
  for (const list of byMonth.values()) {
    const sorted = [...list].sort((a, b) => Number(b.id) - Number(a.id));
    const rep = sorted[0];
    const cutoffNames = sorted
      .map((x) => normalizeCutoffName(x.cutoff_name))
      .filter(Boolean);
    const uniqueCutoffs = Array.from(new Set(cutoffNames)).sort(
      (a, b) => cutoffSortKey(a) - cutoffSortKey(b) || a.localeCompare(b),
    );

    out.push({
      id: rep.id,
      name: rep.name,
      release_date: rep.release_date,
      cutoff_names: uniqueCutoffs,
    });
  }

  out.sort((a, b) => new Date(b.release_date) - new Date(a.release_date));
  return out;
});

const getPeriodLabel = (period) => {
  if (!period) return "";

  // Prefer a clean "Monthly (Month Year)" label based on release_date
  if (period.release_date) {
    const date = new Date(period.release_date);
    if (!Number.isNaN(date.getTime())) {
      const monthYear = date.toLocaleDateString("en-PH", {
        month: "long",
        year: "numeric",
      });
      return `Monthly (${monthYear})`;
    }
  }

  // Fallbacks to whatever backend provided
  return period.name || period.release_date || "";
};

watch(
  () => localForm,
  () => {
    emit("update:formData", { ...localForm });
  },
  { deep: true },
);

watch(
  () => props.formData,
  (val) => {
    Object.assign(localForm, val || {});
  },
  { deep: true },
);

watch(
  () => localSignatories,
  () => {
    emit("update:signatories", { ...localSignatories });
  },
  { deep: true },
);

watch(
  () => props.signatories,
  (val) => {
    Object.assign(localSignatories, val || {});
  },
  { deep: true },
);

// No interval change handling needed for bank remittance
</script>

<style scoped>
.container {
  display: flex;
  justify-content: center;
  width: 100%;
}

.centered-form {
  width: 100%;
}

.groupCard {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
}

.groupHeader {
  font-weight: 600;
  color: #333;
  padding: 0.5rem 0;
  font-size: 1rem;
  line-height: 1.4;
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.period-option-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
}
.period-option-label {
  flex-shrink: 0;
}
.cutoff-tags-inline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  flex-shrink: 0;
}
</style>
