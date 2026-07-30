<template>
  <el-dialog
    v-model="model"
    :title="undefined"
    width="860px"
    destroy-on-close
    align-center
    :close-on-click-modal="false"
    class="fs-dialog"
  >
    <template #header>
      <div class="dialog-header">
        <div class="dialog-title">
          {{ formTitle }}
          <el-tag size="small" effect="plain" type="info" class="ml-3"
            >Fix Schedule</el-tag
          >
        </div>
        <div class="dialog-subtitle">
          Define a schedule name and per-day time details.
        </div>
      </div>
    </template>
    <el-alert type="info" show-icon class="mb-3" :closable="false">
      Use the time grid below to define each day's schedule. Times are in
      12-hour format.
    </el-alert>
    <el-form label-width="160px">
      <el-form-item label="Schedule Name" required>
        <el-input
          v-model="header.name"
          placeholder="e.g. Regular 8-5"
          clearable
        />
      </el-form-item>
      <el-divider>Day Details</el-divider>
      <div class="table-actions mb-3">
        <div class="left-actions">
          <el-button size="small" @click="copyMondayToAll"
            >Copy Monday time schedule to rest of the days</el-button
          >
        </div>
        <div class="right-actions">
          <el-switch v-model="showND" active-text="Show Night Differential" />
        </div>
      </div>
      <el-table
        :data="days"
        :row-key="(row) => row.day_id"
        border
        stripe
        size="small"
        height="420"
        class="fs-table"
        :header-cell-style="{
          background: '#f1f5f9',
          color: '#0f172a',
          fontWeight: 700,
        }"
        :cell-style="{ padding: '8px 10px' }"
      >
        <el-table-column label="Restday" width="72" align="center">
          <template #default="{ row }">
            <el-checkbox
              v-model="row.is_restday"
              @change="onRestdayToggle(row)"
            />
          </template>
        </el-table-column>
        <el-table-column
          prop="name"
          label="Day"
          width="120"
          show-overflow-tooltip
        />
        <el-table-column label="WFH" width="72" align="center">
          <template #default="{ row }">
            <template v-if="!row.is_restday">
              <el-switch v-model="row.is_wfh" />
            </template>
          </template>
        </el-table-column>
        <el-table-column label="Times" align="center">
          <el-table-column label="AM - In" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker
                  size="small"
                  v-model="row.am_in"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  @change="() => recalcWorkHours(row)"
                  clearable
                />
              </template>
            </template>
          </el-table-column>
          <!-- <el-table-column label="AM - Out" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.am_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column> -->
          <!-- <el-table-column label="Break - In" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.break_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column> -->
          <!-- <el-table-column label="Break - Out" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.break_out" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column> -->
          <!-- <el-table-column label="PM - In" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker size="small" v-model="row.pm_in" placeholder="--:--" format="hh:mm A" value-format="HH:mm" @change="() => recalcWorkHours(row)" clearable />
              </template>
            </template>
          </el-table-column> -->
          <el-table-column label="PM - Out" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker
                  size="small"
                  v-model="row.pm_out"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  @change="() => recalcWorkHours(row)"
                  clearable
                />
              </template>
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column
          v-if="showND"
          label="Night Differential"
          align="center"
        >
          <el-table-column label="With ND" width="86" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-switch v-model="row.with_nd" />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="Start" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker
                  size="small"
                  v-model="row.nd_start"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  :disabled="!row.with_nd"
                  clearable
                />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="End" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-time-picker
                  size="small"
                  v-model="row.nd_end"
                  placeholder="--:--"
                  format="hh:mm A"
                  value-format="HH:mm"
                  :disabled="!row.with_nd"
                  clearable
                />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="Rate" width="110" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-input
                  size="small"
                  v-model="row.nd_rate"
                  type="number"
                  step="0.01"
                  :disabled="!row.with_nd"
                  clearable
                />
              </template>
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="Other" align="center">
          <el-table-column label="Grace (min)" width="120" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-input
                  size="small"
                  v-model.number="row.grace_period"
                  type="number"
                  min="0"
                  :disabled="Number(row.flexi_hours || 0) > 0"
                  @change="() => syncFlexGrace(row, 'grace')"
                />
              </template>
            </template>
          </el-table-column>
          <el-table-column label="Flexi (hrs)" width="120" align="center">
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-input
                  size="small"
                  v-model.number="row.flexi_hours"
                  type="number"
                  min="0"
                  step="0.5"
                  :disabled="Number(row.grace_period || 0) > 0"
                  @change="() => syncFlexGrace(row, 'flexi')"
                />
              </template>
            </template>
          </el-table-column>
          <el-table-column width="120" align="center">
            <template #header>
              <div style="display: flex; flex-direction: column; align-items: center; line-height: 1.3;">
                <span>Work Hours</span>
                <span style="font-size: 10px; font-weight: 400; color: #64748b;">(1hr break)</span>
              </div>
            </template>
            <template #default="{ row }">
              <template v-if="!row.is_restday">
                <el-input
                  size="small"
                  v-model.number="row.work_hours"
                  type="number"
                  min="0"
                  step="0.01"
                  readonly
                />
              </template>
            </template>
          </el-table-column>
        </el-table-column>
      </el-table>
    </el-form>

    <template #footer>
      <div class="footer-actions">
        <el-button @click="emit('update:modelValue', false)"
          ><i class="el-icon-close mr-1"></i>Cancel</el-button
        >
        <el-button type="primary" :loading="saving" @click="onSave"
          ><i class="el-icon-check mr-1"></i>Save</el-button
        >
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue";
import { fixScheduleService } from "../../services/api";
import { notify } from "../../services/notify";

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  editId: { type: [Number, String], default: 0 },
});
const emit = defineEmits(["update:modelValue", "saved"]);

const model = computed({
  get: () => props.modelValue,
  set: (v) => emit("update:modelValue", v),
});

const header = reactive({ id: 0, name: "" });
const days = ref([]);
const saving = ref(false);
const formTitle = computed(() =>
  props.editId ? "Edit Fix Schedule" : "New Fix Schedule",
);
const showND = ref(false);
function toBoolish(v) {
  return v === true || v === 1 || v === "1";
}

watch(
  () => props.editId,
  async (id) => {
    if (!model.value) return;
    await loadForm(id);
  },
);

watch(model, async (open) => {
  if (open) {
    await loadForm(props.editId);
  } else {
    reset();
  }
});

function reset() {
  header.id = 0;
  header.name = "";
  days.value = [];
  showND.value = false;
}

async function loadForm(id) {
  const res = await fixScheduleService.form(Number(id || 0));
  // backend returns { data, message } per ApiResponse typically
  const payload = res.data ?? res;
  const fs = payload.fix_schedules?.[0] || { id: 0, name: "" };
  header.id = fs.id || 0;
  header.name = fs.name || "";
  days.value = (payload.days || []).map((d) => {
    const day = {
      id: d.id ?? 0,
      day_id: d.day_id,
      name: d.name,
      is_restday: toBoolish(d.is_restday),
      is_wfh:
        d.is_wfh !== undefined && d.is_wfh !== null
          ? toBoolish(d.is_wfh)
          : Number(d.day_id) === 5,
      am_in: d.am_in,
      // am_out: d.am_out,
      // break_in: d.break_in,
      // break_out: d.break_out,
      // pm_in: d.pm_in,
      pm_out: d.pm_out,
      with_nd: toBoolish(d.with_nd),
      nd_start: d.nd_start,
      nd_end: d.nd_end,
      nd_rate: d.nd_rate,
      grace_period: d.grace_period ?? 0,
      flexi_hours: d.flexi_hours ?? 0,
      work_hours: d.work_hours ?? 8,
    };
    // Recalculate work hours to ensure consistency with frontend calculation
    recalcWorkHours(day);
    syncFlexGrace(day);
    return day;
  });
}

function onRestdayToggle(row) {
  if (row.is_restday) {
    row.with_nd = false;
    row.is_wfh = false;
    row.nd_start = null;
    row.nd_end = null;
    row.nd_rate = null;
    row.am_in = null;
    // row.am_out = null;
    // row.break_in = null;
    // row.break_out = null;
    // row.pm_in = null;
    row.pm_out = null;
    row.grace_period = 0;
    row.flexi_hours = 0;
    row.work_hours = 0;
  }
}
// Parse HH:mm or HH:mm:ss into minutes since midnight
function parseMinutes(value) {
  if (!value) return null;
  // Remove seconds if present (HH:mm:ss -> HH:mm)
  const timeStr = String(value).split(":").slice(0, 2).join(":");
  const [hh, mm] = timeStr.split(":");
  const h = Number(hh),
    m = Number(mm);
  if (!Number.isFinite(h) || !Number.isFinite(m)) return null;
  if (h < 0 || h > 23 || m < 0 || m > 59) return null;
  return h * 60 + m;
}
// Compute hours between two times, allowing for overnight (cross-midnight) segments
function diffHours(start, end) {
  const s = parseMinutes(start);
  const e = parseMinutes(end);
  if (s === null || e === null) return 0;
  // Handle same-day times (end >= start) or overnight (end < start)
  const minutes = e >= s ? e - s : e + 24 * 60 - s;
  return minutes > 0 ? minutes / 60.0 : 0;
}
function recalcWorkHours(row) {
  if (row.is_restday) {
    row.work_hours = 0;
    return;
  }
  // Work hours = pm_out - am_in - 1 hour break time
  // const am = diffHours(row.am_in, row.am_out);
  // const pm = diffHours(row.pm_in, row.pm_out);
  // let total = am + pm;
  let total = diffHours(row.am_in, row.pm_out);
  // Subtract 1 hour for break time
  total = total - 1;
  if (!Number.isFinite(total) || total < 0) total = 0;
  // Round to 2 decimal places to match backend
  row.work_hours = Math.round(total * 100) / 100;
}

function syncFlexGrace(row, source = null) {
  const grace = Number(row.grace_period) || 0;
  const flexi = Number(row.flexi_hours) || 0;
  row.grace_period = grace;
  row.flexi_hours = flexi;

  if (source === "grace") {
    if (grace > 0) row.flexi_hours = 0;
  } else if (source === "flexi") {
    if (flexi > 0) row.grace_period = 0;
  } else if (grace > 0 && flexi > 0) {
    // Default to keeping grace_period when both values exist from backend data
    row.flexi_hours = 0;
  }
}

async function onSave() {
  saving.value = true;
  let toastRef = null;
  try {
    // Basic client-side validation to avoid backend 500 on validation failure
    const name = String(header.name || "").trim();
    if (!name) {
      notify.error("Schedule name is required");
      return;
    }
    // Prevent duplicate names (case-insensitive) excluding current id
    try {
      const existing = await fixScheduleService.list();
      const exists = (existing || []).some(
        (x) =>
          String(x.name || "")
            .trim()
            .toLowerCase() === name.toLowerCase() &&
          Number(x.id) !== Number(header.id || 0),
      );
      if (exists) {
        notify.error("Schedule name already exists");
        return;
      }
    } catch (_) {
      /* best-effort precheck; ignore failures */
    }
    const payload = buildSubmitPayload();
    const id = Number(header.id || 0);
    toastRef = notify.loading(
      id ? "Updating fix schedule..." : "Saving fix schedule...",
    );
    await fixScheduleService.save(id, payload);
    notify.success("Fix schedule saved successfully", { replace: toastRef });
    toastRef = null;
    emit("saved");
    emit("update:modelValue", false);
  } catch (err) {
    const errorMessage = err?.message || "Failed to save fix schedule";
    if (toastRef) {
      notify.error(errorMessage, { replace: toastRef });
      toastRef = null;
    } else {
      notify.error(errorMessage);
    }
  } finally {
    if (toastRef) {
      notify.close(toastRef);
    }
    saving.value = false;
  }
}

function buildSubmitPayload() {
  // The backend expects arrays: day_id[], name[], id[], am_in[], ... with_nd indexed by day_id
  const toNull = (v) => (v === undefined || v === null || v === "" ? null : v);
  const toNum = (v) => {
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
  };
  const toTime = (v) => {
    const val = toNull(v);
    if (val === null) return null;
    // Accept HH:mm or HH:mm:ss; normalize to HH:mm:ss to satisfy SQL time(0)
    return String(val).length === 5 ? `${val}:00` : String(val);
  };

  const p = {
    schedule_name: header.name,
    day_id: [],
    day_name: [],
    id: [],
    is_restday: [],
    am_in: [],
    // am_out: [],
    // break_in: [],
    // break_out: [],
    // pm_in: [],
    pm_out: [],
    nd_start: [],
    nd_end: [],
    nd_rate: [],
    grace_period: [],
    flexi_hours: [],
    work_hours: [],
    with_nd: {},
    is_wfh: [],
  };
  days.value.forEach((d) => {
    p.day_id.push(Number(d.day_id));
    p.day_name.push(toNull(d.name));
    p.id.push(Number(d.id || 0));
    p.is_restday.push(d.is_restday ? 1 : 0);
    p.is_wfh.push(d.is_wfh ? 1 : 0);
    p.am_in.push(toTime(d.am_in));
    // p.am_out.push(toTime(d.am_out));
    // p.break_in.push(toTime(d.break_in));
    // p.break_out.push(toTime(d.break_out));
    // p.pm_in.push(toTime(d.pm_in));
    p.pm_out.push(toTime(d.pm_out));
    p.nd_start.push(toTime(d.nd_start));
    p.nd_end.push(toTime(d.nd_end));
    p.nd_rate.push(toNum(d.nd_rate));
    p.grace_period.push(toNum(d.grace_period));
    p.flexi_hours.push(toNum(d.flexi_hours));
    p.work_hours.push(toNum(d.work_hours));
    if (d.with_nd) p.with_nd[d.day_id] = true;
  });
  return p;
}

function copyMondayToAll() {
  // Day ids from backend are ordered Mon..Sun typically starting with 1; use index 0 as Monday
  if (!days.value || days.value.length === 0) return;
  const monday = days.value[0];
  if (!monday) return;
  const clone = (v) => (v === undefined ? null : JSON.parse(JSON.stringify(v)));
  days.value = days.value.map((d, idx) => {
    if (idx === 0) return d;
    const updated = {
      ...d,
      is_restday: !!monday.is_restday,
      am_in: clone(monday.am_in),
      // am_out: clone(monday.am_out),
      // break_in: clone(monday.break_in),
      // break_out: clone(monday.break_out),
      // pm_in: clone(monday.pm_in),
      pm_out: clone(monday.pm_out),
      with_nd: !!monday.with_nd,
      is_wfh: !!monday.is_wfh,
      nd_start: clone(monday.nd_start),
      nd_end: clone(monday.nd_end),
      nd_rate: monday.nd_rate,
      grace_period: monday.grace_period,
      flexi_hours: monday.flexi_hours,
      work_hours: monday.work_hours,
    };
    // Recalculate work hours to ensure consistency
    recalcWorkHours(updated);
    const source =
      Number(updated.grace_period) > 0
        ? "grace"
        : Number(updated.flexi_hours) > 0
          ? "flexi"
          : null;
    syncFlexGrace(updated, source);
    return updated;
  });
}

// Apply bulk values to selected days
function applyBulk(payload) {
  if (!payload || !Array.isArray(payload.days)) return;
  const values = payload.values || {};
  const toNull = (v) => (v === undefined || v === null || v === "" ? null : v);
  days.value = days.value.map((d) => {
    if (!payload.days.includes(Number(d.day_id))) return d;
    const updated = { ...d };
    const setIf = (key, val) => {
      const v = toNull(val);
      if (v !== null) updated[key] = v;
    };
    setIf("am_in", values.am_in);
    // setIf("am_out", values.am_out);
    // setIf("break_in", values.break_in);
    // setIf("break_out", values.break_out);
    // setIf("pm_in", values.pm_in);
    setIf("pm_out", values.pm_out);
    if (values.with_nd !== undefined) updated.with_nd = !!values.with_nd;
    if (values.is_wfh !== undefined) updated.is_wfh = !!values.is_wfh;
    setIf("nd_start", values.nd_start);
    setIf("nd_end", values.nd_end);
    if (
      values.nd_rate !== undefined &&
      values.nd_rate !== null &&
      values.nd_rate !== ""
    )
      updated.nd_rate = Number(values.nd_rate);
    if (
      values.grace_period !== undefined &&
      values.grace_period !== null &&
      values.grace_period !== ""
    )
      updated.grace_period = Number(values.grace_period);
    if (
      values.flexi_hours !== undefined &&
      values.flexi_hours !== null &&
      values.flexi_hours !== ""
    )
      updated.flexi_hours = Number(values.flexi_hours);
    // Recalculate work hours based on times (don't use work_hours from bulk dialog)
    recalcWorkHours(updated);
    const graceSource = Number(values.grace_period) > 0;
    const flexiSource = Number(values.flexi_hours) > 0;
    syncFlexGrace(
      updated,
      graceSource ? "grace" : flexiSource ? "flexi" : null,
    );
    return updated;
  });
}

defineExpose({ applyBulk });
</script>

<style scoped>
.ml-3 {
  margin-left: 12px;
}
.mb-3 {
  margin-bottom: 12px;
}
.hint {
  color: #64748b;
  font-size: 12px;
  margin-top: 6px;
}
.dialog-header {
  display: flex;
  flex-direction: column;
}
.dialog-title {
  font-weight: 700;
  font-size: 16px;
  color: #0f172a;
  display: flex;
  align-items: center;
}
.dialog-subtitle {
  color: #64748b;
  font-size: 12px;
  margin-top: 2px;
}
.footer-actions {
  display: flex;
  justify-content: flex-end;
  width: 100%;
  gap: 8px;
}
.fs-table :deep(.el-table__header-wrapper) {
  position: sticky;
  top: 0;
  z-index: 1;
}
.table-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.left-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.right-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}
</style>
