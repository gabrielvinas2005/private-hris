<template>
  <MainLayout>
    <template #header>
      <div class="page-header"><div class="title">Approvers Setup</div></div>
    </template>

    <el-card shadow="never" class="block-card">
      <div class="filters-row">
        <div class="filters-left">
          <el-input v-model="search" placeholder="Search by branch/office..." clearable class="search-input" />
          <el-select
            v-model="approverTypeFilter"
            placeholder="All types"
            clearable
            filterable
            class="approver-type-filter"
          >
            <el-option
              v-for="opt in approverTypeFilterOptions"
              :key="'approver-type-opt-' + String(opt.value)"
              :label="opt.label"
              :value="opt.value"
            />
          </el-select>
        </div>
        <div class="actions">
          <el-button type="primary" @click="handleAdd">Add Approver</el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never" class="block-card">
      <div class="export-row">
        <div class="export-buttons">
          <el-button @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
          <el-button @click="handleExportExcel"><el-icon><Download /></el-icon> Excel</el-button>
          <el-button @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
        </div>
        <el-dropdown trigger="click">
          <el-button>
            <el-icon><Setting /></el-icon>
            Column Visibility
            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
          </el-button>
          <template #dropdown>
            <el-dropdown-menu class="column-visibility">
              <el-dropdown-item v-for="(val,key) in columnVisibility" :key="key" disabled>
                <el-checkbox v-model="columnVisibility[key]">{{ getColumnLabel(key) }}</el-checkbox>
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>

    <el-card shadow="hover" class="approvers-table-card">
      <template #header>
        <div class="approvers-table-card-header">
          <div class="approvers-table-card-title">
            <span class="approvers-table-heading">Approver records</span>
            <span class="approvers-table-sub">Use filters above to narrow office and approver type</span>
          </div>
          <el-tag v-if="!loading" type="primary" effect="plain" round size="small" class="approvers-count-tag">
            {{ filteredRows.length }} {{ filteredRows.length === 1 ? 'record' : 'records' }}
          </el-tag>
          <span v-else class="approvers-count-loading" aria-hidden="true">…</span>
        </div>
      </template>
      <div v-if="loading" class="loading-placeholder">
        <el-skeleton :rows="6" animated />
      </div>
      <div v-else-if="filteredRows.length > 0" class="approvers-table-wrap">
        <el-table
          :data="filteredRows"
          class="approvers-data-table"
          stripe
          border
          style="width: 100%"
        >
          <el-table-column v-if="columnVisibility.serial" label="#" width="56" align="center">
            <template #default="{ $index }">
              <span class="row-index">{{ $index + 1 }}</span>
            </template>
          </el-table-column>

          <el-table-column v-if="columnVisibility.office" prop="department" label="Office" min-width="200" sortable show-overflow-tooltip />
          <el-table-column v-if="columnVisibility.section" prop="section" label="Section" min-width="160" sortable show-overflow-tooltip />
          <el-table-column v-if="columnVisibility.approver_type" prop="approver_type" label="Approver Type" min-width="180" sortable show-overflow-tooltip />

          <el-table-column v-if="columnVisibility.approvers" label="Approvers" min-width="280" class-name="td-approvers-chain">
            <template #default="{ row }">
              <div class="approvers-chain">
                <div
                  v-for="slot in getApproverChainSlots(row)"
                  :key="slot.key"
                  class="approvers-chain-row"
                >
                  <span class="approvers-chain-label">{{ slot.label }}</span>
                  <span class="approvers-chain-name" :title="slot.name || ''">{{ slot.name || '—' }}</span>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column v-if="columnVisibility.office_subordinates" label="Office Subordinates" min-width="260" class-name="td-subordinates-form">
            <template #default="{ row }">
              <div v-if="row.office_subordinates && row.office_subordinates.length > 0" class="subordinates-mini-form">
                <div class="subordinates-mini-form-head">
                  <span class="subordinates-mini-form-title">Employees</span>
                  <span class="subordinates-mini-form-count">{{ row.office_subordinates.length }}</span>
                </div>
                <div class="subordinates-mini-form-body">
                  <div
                    v-for="(sub, idx) in row.office_subordinates"
                    :key="sub.id"
                    class="subordinates-mini-form-row"
                  >
                    <span class="subordinates-mini-form-idx">{{ idx + 1 }}</span>
                    <span class="subordinates-mini-form-name" :title="sub.name">{{ sub.name }}</span>
                  </div>
                </div>
              </div>
              <span v-else class="cell-empty">—</span>
            </template>
          </el-table-column>

          <el-table-column v-if="columnVisibility.actions" label="Actions" width="148" fixed="right" align="center">
            <template #default="{ row }">
              <div class="table-actions">
                <el-button type="primary" link size="small" :icon="EditPen" @click="handleEdit(row)">
                  Edit
                </el-button>
                <el-popconfirm title="Delete this approver setup?" confirm-button-text="Yes" cancel-button-text="No" @confirm="handleDelete(row)">
                  <template #reference>
                    <el-button type="danger" link size="small" :icon="Delete">
                      Delete
                    </el-button>
                  </template>
                </el-popconfirm>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div v-else class="no-data">
        <el-empty description="No approvers match your search" />
        <div v-if="hasActiveFilters" class="no-data-extra">
          <el-button type="primary" link @click="clearListFilters">Clear filters</el-button>
        </div>
      </div>
    </el-card>

    <el-dialog
      v-model="formVisible"
      title="Approver Setup"
      width="920px"
      append-to-body
      class="approver-setup-dialog"
      align-center
    >
      <div v-loading="formLoading" class="approver-dialog-body">
        <el-form :model="form" label-width="168px" label-position="left" class="approver-form">
          <el-card class="form-section-card" shadow="never">
            <template #header>
              <div class="section-header">
                <span class="section-header-badge" aria-hidden="true" />
                <el-icon><OfficeBuilding /></el-icon>
                <span>Organizational Structure</span>
              </div>
            </template>
            <el-row :gutter="24" class="form-field-row">
              <el-col :span="12" :xs="24">
                <el-form-item label="Agency" required>
                  <el-select v-model="form.branch_id" placeholder="Select Agency" filterable class="field-full approver-select" @change="onBranchChange" clearable>
                    <el-option v-for="b in options.branches" :key="b.id" :label="b.name" :value="b.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24">
                <el-form-item label="Department" required>
                  <el-select v-model="form.department_id" placeholder="Select Department" filterable class="field-full approver-select" @change="onDepartmentChange" clearable :disabled="!form.branch_id">
                    <el-option v-for="d in options.departments" :key="d.id" :label="d.name" :value="d.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24" class="form-field-row">
              <el-col :span="12" :xs="24">
                <el-form-item label="Division">
                  <el-select v-model="form.division_id" placeholder="Select Division" clearable filterable class="field-full approver-select" @change="onDivisionChange" :disabled="!form.department_id">
                    <el-option v-for="dv in options.divisions" :key="dv.id" :label="dv.name" :value="dv.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24">
                <el-form-item label="Section">
                  <el-select v-model="form.section_id" placeholder="Select Section" clearable filterable class="field-full approver-select" :disabled="!form.division_id">
                    <el-option v-for="s in options.sections" :key="s.id" :label="s.name" :value="s.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <el-card class="form-section-card" shadow="never">
            <template #header>
              <div class="section-header">
                <span class="section-header-badge" aria-hidden="true" />
                <el-icon><CollectionTag /></el-icon>
                <span>Approver Type</span>
              </div>
            </template>
            <el-row :gutter="24" class="form-field-row">
              <el-col :span="12" :xs="24">
                <el-form-item label="Type" required>
                  <el-select v-model="form.type_id" placeholder="Select Approver Type" filterable class="field-full approver-select" clearable>
                    <el-option v-for="t in options.approverTypes" :key="t.id" :label="t.name" :value="t.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <el-card class="form-section-card form-section-card--approvers" shadow="never">
            <template #header>
              <div class="section-header">
                <span class="section-header-badge" aria-hidden="true" />
                <el-icon><UserFilled /></el-icon>
                <span>Approvers</span>
              </div>
            </template>
            <el-row :gutter="24" class="form-field-row">
              <el-col :span="12" :xs="24">
                <el-form-item label="First Approver" required>
                  <el-select v-model="form.approver_1" placeholder="Select First Approver" filterable class="field-full approver-select" clearable>
                    <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24">
                <el-form-item label="Second Approver">
                  <el-select v-model="form.approver_2" placeholder="Select Second Approver" filterable clearable class="field-full approver-select">
                    <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24" class="form-field-row form-field-row--third">
              <el-col :span="12" :xs="24">
                <el-form-item label="Third Approver">
                  <el-select v-model="form.approver_3" placeholder="select Third Approver" clearable filterable class="field-full approver-select">
                    <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24" class="form-field-row">
              <el-col :span="24">
                <el-form-item label="Office Subordinates" class="office-subordinates-item">
                  <div class="office-subordinates-block">
                    <div class="subordinates-toolbar">
                      <el-select
                        v-model="newOfficeSubordinates"
                        multiple
                        filterable
                        clearable
                        collapse-tags
                        collapse-tags-tooltip
                        class="subordinates-select approver-select"
                        :placeholder="form.department_id ? 'Search and select employees…' : 'Please select an office first'"
                        :disabled="!form.department_id"
                      >
                        <el-option v-for="a in options.departmentEmployees" :key="a.id" :label="a.name" :value="a.id" />
                      </el-select>
                      <el-button type="primary" class="subordinates-add-btn" :icon="Plus" :disabled="!newOfficeSubordinates || newOfficeSubordinates.length===0 || !form.department_id" @click="addOfficeSubordinates">
                        Add
                      </el-button>
                    </div>
                    <div class="subordinates-chips">
                      <template v-if="subordinates.department && subordinates.department.length > 0">
                        <el-tag
                          v-for="sub in subordinates.department"
                          :key="sub.id"
                          class="subordinate-tag"
                          closable
                          type="info"
                          effect="plain"
                          round
                          @close="removeOfficeSubordinate(sub.id)"
                        >
                          {{ sub.name }}
                        </el-tag>
                      </template>
                      <p v-else-if="!form.department_id" class="subordinates-hint">Choose a department above to load employees for this office.</p>
                      <p v-else class="subordinates-hint muted">No subordinates yet — select names and click Add.</p>
                    </div>
                  </div>
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveForm">Save</el-button>
        </div>
      </template>
    </el-dialog>
  </MainLayout>
</template>

<script setup>
import { onMounted, ref, computed, watch } from 'vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import { useApprovers } from '../../../composables/useApprovers.js'
import { useExport } from '../../../composables/useExport.js'
import {
  Printer,
  Download,
  Document,
  Setting,
  ArrowDown,
  OfficeBuilding,
  CollectionTag,
  UserFilled,
  EditPen,
  Delete,
  Plus,
} from '@element-plus/icons-vue'

const {
  rows, loading, fetchList,
  formVisible, formLoading, form, options, saveForm,
  loadDepartments, loadDivisions, loadSections,
  saving, openForm,
  // office subordinates management
  newOfficeSubordinates, reloadOfficeSubordinates, loadAvailableSubordinates, addOfficeSubordinates, removeOfficeSubordinate,
  subordinates, deleteApprover,
} = useApprovers()

onMounted(fetchList)

const search = ref('')
/** '' = all types; '__none__' = rows with no approver type set */
const approverTypeFilter = ref('')

const columnVisibility = ref({
  serial: true,
  office: true,
  section: true,
  approver_type: true,
  approvers: true,
  office_subordinates: true,
  actions: true
})
function getColumnLabel(key) {
  const map = {
    serial: '#', 
    office: 'Office', 
    section: 'Section',
    approver_type: 'Approver Type',
    approvers: 'Approvers',
    office_subordinates: 'Office Subordinates',
    actions: 'Edit'
  }
  return map[key] || key
}

/** Rows for the combined Approvers column (1st–4th chain). */
function getApproverChainSlots(row) {
  const pick = (v) => {
    if (v == null || v === '') return ''
    const s = String(v).trim()
    return s || ''
  }
  return [
    { key: 'a1', label: '1st', name: pick(row.approver_1) },
    { key: 'a2', label: '2nd', name: pick(row.approver_2) },
    { key: 'a3', label: '3rd', name: pick(row.approver_3) },
  ]
}

function formatApproversChainExport(row) {
  return getApproverChainSlots(row)
    .filter((s) => s.name)
    .map((s) => `${s.label}: ${s.name}`)
    .join('; ') || '—'
}

const approverTypeFilterOptions = computed(() => {
  const types = new Set()
  let hasEmptyType = false
  for (const r of rows.value) {
    const t = r.approver_type
    if (t == null || String(t).trim() === '') hasEmptyType = true
    else types.add(String(t).trim())
  }
  const sorted = [...types].sort((a, b) => a.localeCompare(b, undefined, { sensitivity: 'base' }))
  const opts = [{ value: '', label: 'All types' }]
  if (hasEmptyType) opts.push({ value: '__none__', label: 'Unspecified type' })
  sorted.forEach((name) => opts.push({ value: name, label: name }))
  return opts
})

const filteredRows = computed(() => rows.value.filter(r => {
  const target = `${r.department || ''} ${r.section || ''}`.toLowerCase()
  if (search.value && !target.includes(search.value.toLowerCase())) return false
  const ft = approverTypeFilter.value ?? ''
  if (!ft) return true
  const rowType = r.approver_type != null ? String(r.approver_type).trim() : ''
  if (ft === '__none__') return rowType === ''
  return rowType === ft
}))

const hasActiveFilters = computed(() => !!(search.value || (approverTypeFilter.value ?? '')))

function clearListFilters() {
  search.value = ''
  approverTypeFilter.value = ''
}

const { exportPrint, exportExcel, exportPDF } = useExport()

const exportColumns = [
  { key: 'serial', label: '#' },
  { key: 'department', label: 'Office' },
  { key: 'section', label: 'Section' },
  { key: 'approver_type', label: 'Approver Type', formatter: row => row.approver_type || '-' },
  { key: 'approvers', label: 'Approvers', formatter: row => formatApproversChainExport(row) },
  { key: 'office_subordinates', label: 'Office Subordinates', formatter: row => row.office_subordinate_names || '-' },
]

function buildExportPayload() {
  const data = filteredRows.value.map((row, index) => ({
    ...row,
    serial: index + 1,
    office_subordinate_names: row.office_subordinates?.map(sub => sub.name).join(', ') || '',
  }))
  return {
    title: 'Approvers Setup',
    data,
    columns: exportColumns,
    columnVisibility: columnVisibility.value
  }
}

function handleAdd() { openFor(0) }
function handleEdit(row) { openFor(row.id) }
async function openFor(id) { await openForm(id) }

function handlePrint() {
  exportPrint(buildExportPayload())
}
function handleExportExcel() {
  exportExcel(buildExportPayload())
}
function handleExportPdf() {
  exportPDF(buildExportPayload())
}

function onBranchChange(val) { loadDepartments(val) }
function onDepartmentChange(val) { 
  loadDivisions(val)
  if(val) {
    loadAvailableSubordinates()
  } else {
    options.departmentEmployees = []
    subordinates.department = []
  }
}
function onDivisionChange(val) { loadSections(val) }

// reload office subordinates when dialog opens or department changes
watch(formVisible, (v)=>{ if(v) setTimeout(()=>reloadOfficeSubordinates(),0) })
watch(()=>form.department_id, (newDeptId)=>{
  if(newDeptId) {
    loadAvailableSubordinates()
  } else {
    options.departmentEmployees = []
    subordinates.department = []
  }
})
// Re-filter office subordinate options when approver type changes (server excludes by department + type)
watch(() => form.type_id, () => {
  if (formVisible.value && form.department_id) {
    loadAvailableSubordinates()
  }
})

function handleDelete(row) { deleteApprover(row.id) }
</script>

<style scoped>
.page-header { display: flex; align-items: center; }
.title { font-weight: 600; font-size: 18px; }
.block-card { margin-bottom: 12px; }
.filters-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.filters-left { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; min-width: 0; flex: 1; }
.left { display: flex; gap: 10px; align-items: center; }
.search-input { width: 380px; max-width: 100%; }
.approver-type-filter { width: 220px; max-width: 100%; }
.actions { display: flex; gap: 8px; }
.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 10px; }
.form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
.mt { margin-top: 12px; }
.approver-setup-dialog :deep(.el-dialog__header) {
  margin-right: 0;
  padding: 20px 24px 12px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.approver-setup-dialog :deep(.el-dialog__title) {
  font-size: 18px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  letter-spacing: -0.02em;
}
.approver-setup-dialog :deep(.el-dialog__body) {
  padding: 20px 24px 8px;
}
.approver-setup-dialog :deep(.el-dialog__footer) {
  padding: 16px 24px 20px;
  border-top: 1px solid var(--el-border-color-lighter);
  background: var(--el-fill-color-blank);
}
.approver-dialog-body {
  min-height: 120px;
}
.approver-form {
  padding: 0;
}
.form-section-card {
  margin-bottom: 16px;
  border-radius: 10px;
  border: 1px solid var(--el-border-color-light);
  overflow: hidden;
  background: var(--el-bg-color);
}
.form-section-card:last-of-type {
  margin-bottom: 0;
}
.form-section-card :deep(.el-card__header) {
  padding: 12px 16px;
  background: linear-gradient(180deg, var(--el-fill-color-light) 0%, var(--el-fill-color-blank) 100%);
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.form-section-card :deep(.el-card__body) {
  padding: 16px 18px 8px;
}
.section-header {
  position: relative;
  display: flex;
  align-items: center;
  gap: 10px;
  padding-left: 12px;
  font-weight: 600;
  font-size: 15px;
  color: var(--el-text-color-primary);
}
.section-header-badge {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 4px;
  height: 18px;
  border-radius: 4px;
  background: var(--el-color-primary);
  opacity: 0.85;
}
.section-header .el-icon {
  font-size: 20px;
  color: var(--el-color-primary);
}
.field-full {
  width: 100%;
}
.approver-form :deep(.el-form-item__label) {
  font-weight: 500;
  color: var(--el-text-color-regular);
}
.dialog-footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  width: 100%;
}
.subordinates-toolbar {
  display: flex;
  align-items: stretch;
  gap: 12px;
  width: 100%;
}
.subordinates-select {
  flex: 1;
  min-width: 0;
}
.subordinates-chips {
  margin-top: 12px;
  padding: 12px 14px;
  min-height: 52px;
  max-height: 200px;
  overflow-y: auto;
  border-radius: 8px;
  background: var(--el-fill-color-light);
  border: 1px dashed var(--el-border-color);
  display: flex;
  flex-wrap: wrap;
  align-content: flex-start;
  gap: 8px;
  align-items: center;
}
.subordinate-tag {
  max-width: 100%;
}
.subordinate-tag :deep(.el-tag__content) {
  overflow: hidden;
  text-overflow: ellipsis;
}
.subordinates-hint {
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
  color: var(--el-text-color-secondary);
  font-style: normal;
}
.subordinates-hint.muted {
  color: var(--el-text-color-placeholder);
}
.approver-warning { 
  color: #ec4899; 
  font-size: 12px; 
  margin-top: 4px; 
  font-style: italic;
  line-height: 1.3;
}
.subordinates-list { max-height: 200px; overflow-y: auto; }
.subordinate-item { padding: 2px 0; line-height: 1.4; }
.text-muted { color: #999; font-style: italic; }
.loading-placeholder { padding: 8px 4px 20px; }
.no-data { padding: 32px 24px 48px; text-align: center; }
.no-data-extra { margin-top: 8px; }

.approvers-table-card :deep(.el-card__header) {
  padding: 14px 20px;
  border-bottom: 1px solid var(--el-border-color-lighter);
}
.approvers-table-card :deep(.el-card__body) {
  padding: 0 20px 20px;
}
.approvers-table-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}
.approvers-table-card-title {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.approvers-table-heading {
  font-size: 16px;
  font-weight: 600;
  color: var(--el-text-color-primary);
  letter-spacing: -0.02em;
}
.approvers-table-sub {
  font-size: 12px;
  color: var(--el-text-color-secondary);
}
.approvers-count-tag {
  font-weight: 600;
}
.approvers-count-loading {
  font-size: 18px;
  color: var(--el-text-color-placeholder);
  letter-spacing: 0.12em;
}
.approvers-table-wrap {
  overflow-x: auto;
}
.approvers-data-table {
  --el-table-header-bg-color: var(--el-fill-color-light);
}
.approvers-data-table :deep(.el-table__header th) {
  font-weight: 600;
  color: var(--el-text-color-regular);
}
.approvers-data-table :deep(td.td-subordinates-form) {
  vertical-align: top;
  padding-top: 10px;
  padding-bottom: 10px;
}
.approvers-data-table :deep(td.td-approvers-chain) {
  vertical-align: top;
  padding-top: 10px;
  padding-bottom: 10px;
}
.approvers-chain {
  font-size: 12px;
  line-height: 1.45;
}
.approvers-chain-row {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 8px;
  align-items: baseline;
  padding: 3px 0;
  border-bottom: 1px solid var(--el-border-color-extra-light);
}
.approvers-chain-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.approvers-chain-label {
  font-weight: 600;
  color: var(--el-text-color-secondary);
  font-variant-numeric: tabular-nums;
}
.approvers-chain-name {
  color: var(--el-text-color-primary);
  word-break: break-word;
}
.row-index {
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-secondary);
  font-size: 13px;
}
/* Compact “mini form” list inside table cells — fixed height + scroll, does not stretch the row */
.subordinates-mini-form {
  width: 100%;
  max-width: 320px;
  border: 1px solid var(--el-border-color);
  border-radius: 8px;
  background: var(--el-fill-color-blank);
  overflow: hidden;
  vertical-align: top;
}
.subordinates-mini-form-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 6px 10px;
  background: var(--el-fill-color-light);
  border-bottom: 1px solid var(--el-border-color-lighter);
  font-size: 12px;
}
.subordinates-mini-form-title {
  font-weight: 600;
  color: var(--el-text-color-regular);
}
.subordinates-mini-form-count {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
  color: var(--el-color-primary);
  font-size: 12px;
}
.subordinates-mini-form-body {
  max-height: 132px;
  overflow-y: auto;
  padding: 4px 0;
}
.subordinates-mini-form-row {
  display: grid;
  grid-template-columns: 26px minmax(0, 1fr);
  align-items: baseline;
  gap: 6px;
  padding: 5px 10px;
  font-size: 12px;
  line-height: 1.35;
  border-bottom: 1px solid var(--el-border-color-extra-light);
}
.subordinates-mini-form-row:last-child {
  border-bottom: none;
}
.subordinates-mini-form-idx {
  font-variant-numeric: tabular-nums;
  color: var(--el-text-color-placeholder);
  text-align: right;
}
.subordinates-mini-form-name {
  color: var(--el-text-color-primary);
  word-break: break-word;
}
.cell-empty {
  color: var(--el-text-color-placeholder);
  font-size: 14px;
}
.table-actions {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 4px;
}
.table-actions :deep(.el-button) {
  padding: 4px 6px;
}

.form-field-row {
  margin-bottom: 4px;
}
.form-field-row--third :deep(.el-form-item) {
  margin-bottom: 18px;
}
.form-section-card--approvers :deep(.el-card__body) {
  padding-bottom: 12px;
}
.approver-select :deep(.el-input__wrapper) {
  border-radius: 8px;
  transition: box-shadow 0.15s ease;
}
.office-subordinates-item :deep(.el-form-item__content) {
  display: block;
}
.office-subordinates-block {
  width: 100%;
}
.subordinates-add-btn {
  flex-shrink: 0;
  border-radius: 8px;
  padding-left: 14px;
  padding-right: 16px;
}
</style>


