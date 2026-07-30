<template>
  <div>
    <el-form label-width="140px">
      <el-row :gutter="16">
        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Report Information</div>

            <el-form-item label="Division">
              <el-select
                v-model="localForm.division_id"
                placeholder="Select Division"
                clearable
                required
              >
                <el-option
                  v-for="d in divisions"
                  :key="d.id"
                  :label="d.name"
                  :value="d.id"
                />
              </el-select>
            </el-form-item>

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
                  <div class="flex justify-between items-center">
                    <span>
                      {{ getPeriodLabel(p) }}
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

            <el-form-item label="Bank Loan Type">
              <el-select
                v-model="localForm.deduction"
                placeholder="Select Bank Loan Type"
                filterable
                clearable
                required
              >
                <el-option
                  v-for="e in bankLoanTypes"
                  :key="e.id"
                  :label="e.name"
                  :value="e.id"
                />
              </el-select>
            </el-form-item>
          </el-card>
        </el-col>

        <el-col :span="12">
          <el-card class="groupCard">
            <div class="groupHeader">Signatory</div>

            <el-form-item label="Certified Correct">
              <el-input v-model="localSignatories.certified_correct" />
            </el-form-item>

            <el-form-item label="Position/Designation">
              <el-input v-model="localSignatories.position" />
            </el-form-item>

            <el-form-item label="Date">
              <el-date-picker v-model="localSignatories.date" type="date" />
            </el-form-item>
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
  divisions: { type: Array, default: () => [] },
  bankLoanTypes: { type: Array, default: () => [] },
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

// Display as one option per month (with cutoff tags inline)
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
      id: rep.id, // keep v-model as payroll_period_id
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
}

.groupCard {
  border: 1px solid #e0e0e0;
  border-radius: 25px;
}

.groupCard .groupHeader {
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.flex {
  display: flex;
}
.justify-between {
  justify-content: space-between;
}
.items-center {
  align-items: center;
}
</style>
