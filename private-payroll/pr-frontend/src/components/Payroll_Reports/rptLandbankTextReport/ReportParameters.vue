<template>
  <el-form :inline="true" class="mb-3">
    <el-form-item label="Division">
      <el-select
        v-model="localDivisionId"
        placeholder="Select Division"
        style="width: 260px"
        filterable
        clearable
        @change="emitChange"
      >
        <el-option
          v-for="d in divisions"
          :key="d.id"
          :label="d.name"
          :value="d.id"
        />
      </el-select>
    </el-form-item>
    <el-form-item label="Payroll Period">
      <el-select
        v-model="localPayrollPeriodId"
        placeholder="Select payroll period"
        style="width: 320px"
        filterable
        clearable
        @change="emitChange"
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
    <el-form-item>
      <el-button type="primary" :disabled="!canSearch" @click="$emit('search')"
        >Search</el-button
      >
    </el-form-item>
  </el-form>
</template>

<script setup>
import { computed, watch, toRefs, ref } from "vue";
import PayrollCutoffTag from "@/components/Shared/PayrollCutoffTag.vue";

const props = defineProps({
  divisions: { type: Array, default: () => [] },
  payrolls: { type: Array, default: () => [] },
  divisionId: { type: [Number, String], default: "" },
  payrollPeriodId: { type: [Number, String], default: "" },
});

const emit = defineEmits([
  "update:divisionId",
  "update:payrollPeriodId",
  "search",
]);

const { divisionId, payrollPeriodId } = toRefs(props);
const localDivisionId = ref(divisionId.value);
const localPayrollPeriodId = ref(payrollPeriodId.value);

watch(divisionId, (v) => (localDivisionId.value = v));
watch(payrollPeriodId, (v) => (localPayrollPeriodId.value = v));

const normalizeCutoffName = (name) => String(name || "").trim();
const cutoffSortKey = (name) => {
  const s = String(name || "").toLowerCase();
  if (s.includes("1st") || s.includes("first")) return 1;
  if (s.includes("2nd") || s.includes("second")) return 2;
  if (s.includes("monthly")) return 3;
  return 9;
};

const displayPayrollPeriods = computed(() => {
  const periods = Array.isArray(props.payrolls) ? props.payrolls : [];
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
  return period.name || "";
};

const emitChange = () => {
  emit("update:divisionId", localDivisionId.value);
  emit("update:payrollPeriodId", localPayrollPeriodId.value);
};

const canSearch = computed(
  () => !!localDivisionId.value && !!localPayrollPeriodId.value,
);
</script>

<style scoped>
.mb-3 {
  margin-bottom: 12px;
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
