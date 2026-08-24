<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div class="title-wrap">
          <h1 class="title">Approvers Setup</h1>
          <p class="subtitle">Configure multi-level approval hierarchies, request types, and office subordinate coverage</p>
        </div>
      </div>
    </template>

    <!-- Top Analytics Overview Cards -->
    <div class="analytics-overview-grid">
      <el-card shadow="never" class="metric-card">
        <div class="metric-content">
          <div class="metric-icon-box bg-blue">
            <el-icon><List /></el-icon>
          </div>
          <div class="metric-data">
            <span class="metric-label">Total Approval Routes</span>
            <span class="metric-value">{{ totalRoutesCount }}</span>
          </div>
        </div>
      </el-card>

      <el-card shadow="never" class="metric-card">
        <div class="metric-content">
          <div class="metric-icon-box bg-green">
            <el-icon><OfficeBuilding /></el-icon>
          </div>
          <div class="metric-data">
            <span class="metric-label">Covered Offices</span>
            <span class="metric-value">{{ coveredOfficesCount }}</span>
          </div>
        </div>
      </el-card>

      <el-card shadow="never" class="metric-card">
        <div class="metric-content">
          <div class="metric-icon-box bg-purple">
            <el-icon><UserFilled /></el-icon>
          </div>
          <div class="metric-data">
            <span class="metric-label">Coverage Matrix Mode</span>
            <span class="metric-value font-sm">Department Hierarchy</span>
          </div>
        </div>
      </el-card>
    </div>

    <!-- Filters & Action Bar -->
    <el-card shadow="never" class="block-card shadow-sm">
      <div class="filters-row">
        <div class="filters-left">
          <el-input
            v-model="search"
            placeholder="Search by office or section name..."
            clearable
            class="search-input"
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-select
            v-model="approverTypeFilter"
            placeholder="All Request Types"
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
          <el-radio-group v-model="activeView" size="default" class="view-switcher">
            <el-radio-button label="table">
              <el-icon><List /></el-icon> Table View
            </el-radio-button>
            <el-radio-button label="matrix">
              <el-icon><DataAnalysis /></el-icon> Coverage Matrix
            </el-radio-button>
          </el-radio-group>

          <el-button type="primary" size="default" :icon="Plus" class="btn-create-rule" @click="handleAdd">
            Create Approval Rule
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- Table View -->
    <template v-if="activeView === 'table'">
      <el-card shadow="never" class="block-card action-bar-card">
        <div class="export-row">
          <div class="export-buttons">
            <el-button size="small" @click="handlePrint"><el-icon><Printer /></el-icon> Print</el-button>
            <el-button size="small" @click="handleExportExcel"><el-icon><Download /></el-icon> Excel</el-button>
            <el-button size="small" @click="handleExportPdf"><el-icon><Document /></el-icon> PDF</el-button>
          </div>
          <el-dropdown trigger="click">
            <el-button size="small">
              <el-icon><Setting /></el-icon>
              Columns
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

      <el-card shadow="never" class="approvers-table-card shadow-sm">
        <template #header>
          <div class="approvers-table-card-header">
            <div class="approvers-table-card-title">
              <span class="approvers-table-heading">Active Approval Rules</span>
              <span class="approvers-table-sub">Mapped rules for leaves, timekeeping, and official business</span>
            </div>
            <el-tag v-if="!loading" type="primary" effect="plain" round size="small" class="approvers-count-tag">
              {{ filteredRows.length }} {{ filteredRows.length === 1 ? 'rule' : 'rules' }}
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

            <el-table-column v-if="columnVisibility.office" prop="department" label="Office / Department" min-width="200" sortable show-overflow-tooltip>
              <template #default="{ row }">
                <div class="dept-cell">
                  <span class="dept-name">{{ row.department || 'Unassigned Department' }}</span>
                  <span v-if="row.branch" class="branch-sub">{{ row.branch }}</span>
                </div>
              </template>
            </el-table-column>

            <el-table-column v-if="columnVisibility.section" prop="section" label="Section / Division" min-width="160" sortable show-overflow-tooltip>
              <template #default="{ row }">
                <span>{{ row.section || row.division || '—' }}</span>
              </template>
            </el-table-column>

            <el-table-column v-if="columnVisibility.approver_type" prop="approver_type" label="Request Type" min-width="180" sortable show-overflow-tooltip>
              <template #default="{ row }">
                <el-tag v-if="row.approver_type" type="primary" effect="light" round size="small" class="type-tag">
                  {{ row.approver_type }}
                </el-tag>
                <el-tag v-else type="info" effect="plain" round size="small">
                  All / Default
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column v-if="columnVisibility.approvers" label="Approval Pipeline" min-width="280" class-name="td-approvers-chain">
              <template #default="{ row }">
                <div class="pipeline-mini-steps">
                  <div
                    v-for="slot in getApproverChainSlots(row)"
                    :key="slot.key"
                    class="pipeline-mini-step"
                    :class="{ 'has-approver': !!slot.name }"
                  >
                    <span class="step-badge">{{ slot.label }}</span>
                    <span class="step-name">{{ slot.name || 'Not set' }}</span>
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column v-if="columnVisibility.office_subordinates" label="Office Subordinates" min-width="240" class-name="td-subordinates-form">
              <template #default="{ row }">
                <div v-if="row.office_subordinates && row.office_subordinates.length > 0" class="subordinates-mini-form">
                  <div class="subordinates-mini-form-head">
                    <span class="subordinates-mini-form-title">Included Employees</span>
                    <el-tag size="small" type="success" effect="plain" round>{{ row.office_subordinates.length }}</el-tag>
                  </div>
                  <div class="subordinates-mini-form-body">
                    <div
                      v-for="(sub, idx) in row.office_subordinates.slice(0, 3)"
                      :key="sub.id"
                      class="subordinates-mini-form-row"
                    >
                      <span class="subordinates-mini-form-name" :title="sub.name">• {{ sub.name }}</span>
                    </div>
                    <div v-if="row.office_subordinates.length > 3" class="more-subordinates-hint">
                      +{{ row.office_subordinates.length - 3 }} more employees
                    </div>
                  </div>
                </div>
                <span v-else class="cell-empty">All Department Employees (Default)</span>
              </template>
            </el-table-column>

            <el-table-column v-if="columnVisibility.actions" label="Actions" width="160" fixed="right" align="center">
              <template #default="{ row }">
                <div class="table-actions">
                  <el-tooltip content="Duplicate / Clone Setup" placement="top">
                    <el-button type="info" link size="small" :icon="CopyDocument" @click="handleDuplicate(row)" />
                  </el-tooltip>
                  <el-button type="primary" link size="small" :icon="EditPen" @click="handleEdit(row)">
                    Edit
                  </el-button>
                  <el-popconfirm title="Delete this approval rule?" confirm-button-text="Yes" cancel-button-text="No" @confirm="handleDelete(row)">
                    <template #reference>
                      <el-button type="danger" link size="small" :icon="Delete" />
                    </template>
                  </el-popconfirm>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>

        <div v-else class="no-data">
          <el-empty description="No approval rules match your criteria" />
          <div v-if="hasActiveFilters" class="no-data-extra">
            <el-button type="primary" link @click="clearListFilters">Clear filters</el-button>
          </div>
        </div>
      </el-card>
    </template>

    <!-- Coverage Matrix View -->
    <template v-else>
      <el-card shadow="never" class="block-card shadow-sm">
        <template #header>
          <div class="matrix-card-header">
            <div>
              <h2 class="matrix-title">Department Coverage Matrix</h2>
              <p class="matrix-sub">Overview of active approval configurations across all offices</p>
            </div>
          </div>
        </template>

        <div class="coverage-grid">
          <div v-for="item in coverageMatrix" :key="item.department" class="coverage-card">
            <div class="coverage-card-header">
              <div>
                <span class="coverage-dept-title">{{ item.department }}</span>
                <span class="coverage-branch-sub">{{ item.branch }}</span>
              </div>
              <el-tag type="success" effect="dark" round size="small">
                {{ item.rules_count }} {{ item.rules_count === 1 ? 'Rule' : 'Rules' }}
              </el-tag>
            </div>

            <div class="coverage-card-body">
              <div class="coverage-detail-row">
                <span class="detail-label">Configured Types:</span>
                <div class="types-wrap">
                  <el-tag v-for="t in item.types_list" :key="t" size="small" type="info" effect="plain">
                    {{ t }}
                  </el-tag>
                </div>
              </div>

              <div class="coverage-detail-row">
                <span class="detail-label">1st Approver(s):</span>
                <span class="detail-val">{{ item.approver_1_list.join(', ') || 'Not Set' }}</span>
              </div>

              <div class="coverage-detail-row">
                <span class="detail-label">2nd Approver(s):</span>
                <span class="detail-val">{{ item.approver_2_list.join(', ') || 'Not Set' }}</span>
              </div>

              <div class="coverage-detail-row">
                <span class="detail-label">Subordinates:</span>
                <span class="detail-val">{{ item.subordinates_count > 0 ? item.subordinates_count + ' Assigned' : 'All Dept Employees (Default)' }}</span>
              </div>
            </div>
          </div>
        </div>
      </el-card>
    </template>

    <!-- Modal Form Dialog -->
    <el-dialog
      v-model="formVisible"
      :title="isDuplicateMode ? 'Clone Approval Rule' : (form.id ? 'Edit Approval Rule' : 'New Approval Rule')"
      width="900px"
      append-to-body
      class="approver-setup-dialog"
      align-center
    >
      <div v-loading="formLoading" class="approver-dialog-body">
        <el-form :model="form" label-width="160px" label-position="left" class="approver-form">
          
          <!-- Section 1: Organizational Scope -->
          <el-card class="form-section-card" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon class="icon-blue"><OfficeBuilding /></el-icon>
                <span class="section-title">1. Target Office Scope</span>
              </div>
            </template>
            <el-row :gutter="20">
              <el-col :span="12" :xs="24">
                <el-form-item label="Agency / Branch" required>
                  <el-select v-model="form.branch_id" placeholder="Select Agency" filterable class="field-full" @change="onBranchChange" clearable>
                    <el-option v-for="b in options.branches" :key="b.id" :label="b.name" :value="b.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24">
                <el-form-item label="Office / Department" required>
                  <el-select v-model="form.department_id" placeholder="Select Department" filterable class="field-full" @change="onDepartmentChange" clearable :disabled="!form.branch_id">
                    <el-option v-for="d in options.departments" :key="d.id" :label="d.name" :value="d.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="20">
              <el-col :span="12" :xs="24">
                <el-form-item label="Division (Optional)">
                  <el-select v-model="form.division_id" placeholder="All Divisions" clearable filterable class="field-full" @change="onDivisionChange" :disabled="!form.department_id">
                    <el-option v-for="dv in options.divisions" :key="dv.id" :label="dv.name" :value="dv.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12" :xs="24">
                <el-form-item label="Section (Optional)">
                  <el-select v-model="form.section_id" placeholder="All Sections" clearable filterable class="field-full" :disabled="!form.division_id">
                    <el-option v-for="s in options.sections" :key="s.id" :label="s.name" :value="s.id" />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
          </el-card>

          <!-- Section 2: Request Type Bundling -->
          <el-card class="form-section-card" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon class="icon-purple"><CollectionTag /></el-icon>
                <span class="section-title">2. Request Module(s)</span>
              </div>
            </template>
            <el-form-item :label="form.id && !isDuplicateMode ? 'Request Type' : 'Select Modules'" required>
              <!-- Single Select if editing existing rule -->
              <el-select 
                v-if="form.id && !isDuplicateMode"
                v-model="form.type_id" 
                placeholder="Select Request Type" 
                filterable 
                class="field-full" 
                clearable
              >
                <el-option v-for="t in options.approverTypes" :key="t.id" :label="t.name" :value="t.id" />
              </el-select>

              <!-- Multi Select when creating new or duplicating -->
              <div v-else class="multi-type-container">
                <el-select
                  v-model="form.type_ids"
                  multiple
                  collapse-tags
                  collapse-tags-tooltip
                  filterable
                  placeholder="Select one or multiple request types..."
                  class="field-full"
                >
                  <el-option v-for="t in options.approverTypes" :key="t.id" :label="t.name" :value="t.id" />
                </el-select>
                <span class="type-hint">Create identical approval routes across multiple modules in 1 click.</span>
              </div>
            </el-form-item>
          </el-card>

          <!-- Section 3: Visual Approval Pipeline -->
          <el-card class="form-section-card" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon class="icon-green"><UserFilled /></el-icon>
                <span class="section-title">3. Approval Pipeline</span>
              </div>
            </template>
            
            <div class="visual-pipeline-builder">
              <!-- Step 1 -->
              <div class="pipeline-step-card">
                <div class="step-header">
                  <div class="step-num bg-step-1">Step 1</div>
                  <span class="step-title">First Approver</span>
                </div>
                <el-select v-model="form.approver_1" placeholder="Select 1st Approver" filterable clearable class="field-full">
                  <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                </el-select>
              </div>

              <div class="pipeline-connector"><el-icon><ArrowRight /></el-icon></div>

              <!-- Step 2 -->
              <div class="pipeline-step-card">
                <div class="step-header">
                  <div class="step-num bg-step-2">Step 2</div>
                  <span class="step-title">Second Approver</span>
                </div>
                <el-select v-model="form.approver_2" placeholder="Select 2nd Approver (Optional)" filterable clearable class="field-full">
                  <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                </el-select>
              </div>

              <div class="pipeline-connector"><el-icon><ArrowRight /></el-icon></div>

              <!-- Step 3 -->
              <div class="pipeline-step-card">
                <div class="step-header">
                  <div class="step-num bg-step-3">Step 3</div>
                  <span class="step-title">Third Approver</span>
                </div>
                <el-select v-model="form.approver_3" placeholder="Select 3rd Approver (Optional)" filterable clearable class="field-full">
                  <el-option v-for="a in options.approvers" :key="a.id" :label="a.name" :value="a.id" />
                </el-select>
              </div>
            </div>

            <!-- Subordinates Section with 1-Click Auto-Add -->
            <div class="subordinates-section-wrap">
              <div class="subordinates-header-row">
                <div>
                  <span class="sub-header-title">Office Employee Coverage</span>
                  <p class="sub-header-desc">Leave empty to cover all present and future department employees by default</p>
                </div>
                <el-button 
                  type="success" 
                  plain 
                  size="small" 
                  :icon="Check" 
                  :disabled="!form.department_id || !options.departmentEmployees || options.departmentEmployees.length === 0"
                  @click="autoAddAllDepartmentSubordinates"
                >
                  Auto-Add All Department Employees
                </el-button>
              </div>

              <div class="subordinates-toolbar mt-2">
                <el-select
                  v-model="newOfficeSubordinates"
                  multiple
                  filterable
                  clearable
                  collapse-tags
                  collapse-tags-tooltip
                  class="subordinates-select field-full"
                  :placeholder="form.department_id ? 'Select specific employee exceptions...' : 'Select a department above first'"
                  :disabled="!form.department_id"
                >
                  <el-option v-for="a in options.departmentEmployees" :key="a.id" :label="a.name" :value="a.id" />
                </el-select>
                <el-button type="primary" class="subordinates-add-btn" :icon="Plus" :disabled="!newOfficeSubordinates || newOfficeSubordinates.length===0 || !form.department_id" @click="addOfficeSubordinates">
                  Add
                </el-button>
              </div>

              <div class="subordinates-chips mt-2">
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
                <p v-else-if="!form.department_id" class="subordinates-hint">Choose a department above to view employee options.</p>
                <p v-else class="subordinates-hint text-success font-medium">All department employees are automatically included under this rule.</p>
              </div>
            </div>
          </el-card>

        </el-form>
      </div>

      <template #footer>
        <div class="dialog-footer-actions">
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" :loading="saving" @click="saveForm">
            {{ isDuplicateMode ? 'Create Cloned Rule' : 'Save Approval Rule' }}
          </el-button>
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
  ArrowRight,
  OfficeBuilding,
  CollectionTag,
  UserFilled,
  EditPen,
  Delete,
  Plus,
  CopyDocument,
  List,
  DataAnalysis,
  Search,
  Check,
} from '@element-plus/icons-vue'

const {
  rows, loading, fetchList,
  formVisible, formLoading, isDuplicateMode, form, options, saveForm,
  loadDepartments, loadDivisions, loadSections,
  saving, openForm, duplicateApprover,
  // office subordinates management
  newOfficeSubordinates, reloadOfficeSubordinates, loadAvailableSubordinates, autoAddAllDepartmentSubordinates, addOfficeSubordinates, removeOfficeSubordinate,
  subordinates, deleteApprover,
  // analytics
  coverageMatrix, coveredOfficesCount, totalRoutesCount,
} = useApprovers()

onMounted(fetchList)

const search = ref('')
const approverTypeFilter = ref('')
const activeView = ref('table')

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
    actions: 'Actions'
  }
  return map[key] || key
}

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
  const opts = [{ value: '', label: 'All Request Types' }]
  if (hasEmptyType) opts.push({ value: '__none__', label: 'Common / All Types' })
  sorted.forEach((name) => opts.push({ value: name, label: name }))
  return opts
})

const filteredRows = computed(() => rows.value.filter(r => {
  const target = `${r.department || ''} ${r.section || ''} ${r.branch || ''}`.toLowerCase()
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

function handleAdd() { openForm(0) }
function handleEdit(row) { openForm(row.id) }
function handleDuplicate(row) { duplicateApprover(row) }

function handlePrint() { exportPrint(buildExportPayload()) }
function handleExportExcel() { exportExcel(buildExportPayload()) }
function handleExportPdf() { exportPDF(buildExportPayload()) }

function onBranchChange(val) { loadDepartments(val) }
function onDepartmentChange(val) { 
  loadDivisions(val)
  if (val) {
    loadAvailableSubordinates()
  } else {
    options.departmentEmployees = []
    subordinates.department = []
  }
}
function onDivisionChange(val) { loadSections(val) }

watch(formVisible, (v) => { if(v) setTimeout(() => reloadOfficeSubordinates(), 0) })
watch(() => form.department_id, (newDeptId) => {
  if (newDeptId) {
    loadAvailableSubordinates()
  } else {
    options.departmentEmployees = []
    subordinates.department = []
  }
})
watch(() => form.type_id, () => {
  if (formVisible.value && form.department_id) {
    loadAvailableSubordinates()
  }
})

function handleDelete(row) { deleteApprover(row.id) }
</script>

<style scoped>
.page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.title-wrap .title { font-size: 20px; font-weight: 700; color: var(--el-text-color-primary); margin: 0; }
.title-wrap .subtitle { font-size: 13px; color: var(--el-text-color-secondary); margin-top: 2px; }

.analytics-overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 16px; }
.metric-card { border-radius: 12px; border: 1px solid var(--el-border-color-lighter); }
.metric-content { display: flex; align-items: center; gap: 16px; }
.metric-icon-box { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; }
.bg-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.bg-green { background: linear-gradient(135deg, #10b981, #047857); }
.bg-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }

.metric-data { display: flex; flex-direction: column; }
.metric-label { font-size: 12px; color: var(--el-text-color-secondary); font-weight: 500; }
.metric-value { font-size: 22px; font-weight: 700; color: var(--el-text-color-primary); }
.font-sm { font-size: 15px; }

.block-card { margin-bottom: 16px; border-radius: 12px; }
.shadow-sm { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }

.filters-row { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
.filters-left { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; flex: 1; }
.search-input { width: 340px; max-width: 100%; }
.approver-type-filter { width: 200px; max-width: 100%; }

.actions { display: flex; align-items: center; gap: 16px; }
.view-switcher { margin-right: 4px; }
.btn-create-rule { font-weight: 600; border-radius: 8px; }

.export-row { display: flex; justify-content: space-between; align-items: center; }
.export-buttons { display: flex; gap: 8px; }

.dept-cell { display: flex; flex-direction: column; }
.dept-name { font-weight: 600; color: var(--el-text-color-primary); }
.branch-sub { font-size: 11px; color: var(--el-text-color-secondary); }

.pipeline-mini-steps { display: flex; flex-direction: column; gap: 4px; }
.pipeline-mini-step { display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--el-text-color-placeholder); }
.pipeline-mini-step.has-approver { color: var(--el-text-color-primary); }
.step-badge { font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 4px; background: var(--el-fill-color-dark); color: white; }
.step-name { font-weight: 500; }

.subordinates-mini-form { display: flex; flex-direction: column; gap: 4px; }
.subordinates-mini-form-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px; }
.subordinates-mini-form-title { font-size: 11px; font-weight: 600; color: var(--el-text-color-secondary); text-transform: uppercase; }
.more-subordinates-hint { font-size: 11px; color: var(--el-color-primary); font-weight: 600; margin-top: 2px; }

.table-actions { display: flex; gap: 6px; align-items: center; justify-content: center; }

/* Matrix View */
.matrix-card-header { display: flex; justify-content: space-between; align-items: center; }
.matrix-title { font-size: 16px; font-weight: 700; margin: 0; }
.matrix-sub { font-size: 12px; color: var(--el-text-color-secondary); margin: 2px 0 0 0; }

.coverage-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 12px; }
.coverage-card { border: 1px solid var(--el-border-color-lighter); border-radius: 10px; padding: 14px; background: var(--el-bg-color); transition: all 0.2s ease; }
.coverage-card:hover { border-color: var(--el-color-primary-light-5); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.coverage-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--el-border-color-extra-light); }
.coverage-dept-title { font-weight: 700; font-size: 14px; display: block; }
.coverage-branch-sub { font-size: 11px; color: var(--el-text-color-secondary); }

.coverage-detail-row { display: flex; flex-direction: column; gap: 2px; margin-bottom: 8px; font-size: 12px; }
.detail-label { color: var(--el-text-color-secondary); font-weight: 500; }
.detail-val { font-weight: 600; color: var(--el-text-color-primary); }
.types-wrap { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 2px; }

/* Dialog Styling */
.form-section-card { margin-bottom: 16px; border-radius: 10px; }
.section-header { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; }
.icon-blue { color: #3b82f6; font-size: 18px; }
.icon-purple { color: #8b5cf6; font-size: 18px; }
.icon-green { color: #10b981; font-size: 18px; }

.field-full { width: 100%; }

.multi-type-container { display: flex; flex-direction: column; gap: 4px; width: 100%; }
.type-hint { font-size: 11px; color: var(--el-text-color-secondary); }

.visual-pipeline-builder { display: flex; align-items: center; gap: 12px; padding: 12px 0; overflow-x: auto; }
.pipeline-step-card { flex: 1; min-width: 200px; background: var(--el-fill-color-blank); border: 1px solid var(--el-border-color); border-radius: 8px; padding: 12px; }
.step-header { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.step-num { font-size: 10px; font-weight: 800; color: white; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
.bg-step-1 { background: #3b82f6; }
.bg-step-2 { background: #8b5cf6; }
.bg-step-3 { background: #ec4899; }
.step-title { font-weight: 600; font-size: 12px; }

.pipeline-connector { color: var(--el-text-color-placeholder); font-size: 18px; display: flex; align-items: center; }

.subordinates-section-wrap { border-top: 1px solid var(--el-border-color-extra-light); padding-top: 16px; margin-top: 12px; }
.subordinates-header-row { display: flex; justify-content: space-between; align-items: center; }
.sub-header-title { font-weight: 700; font-size: 13px; }
.sub-header-desc { font-size: 11px; color: var(--el-text-color-secondary); margin: 0; }

.subordinates-toolbar { display: flex; gap: 8px; align-items: center; }
.subordinates-select { flex: 1; }

.subordinates-chips { display: flex; flex-wrap: wrap; gap: 6px; max-height: 120px; overflow-y: auto; padding: 4px; }
.subordinate-tag { font-size: 12px; }
.subordinates-hint { font-size: 12px; color: var(--el-text-color-secondary); margin: 4px 0 0 0; }
.text-success { color: var(--el-color-success); }
.font-medium { font-weight: 500; }
</style>
