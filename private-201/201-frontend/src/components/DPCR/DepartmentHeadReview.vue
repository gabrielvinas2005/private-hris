<template>
  <el-dialog
    v-model="visible"
    :title="title"
    width="90%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
    top="5vh"
  >
    <div v-loading="loading">
      <div v-if="dpcrInfo">
        <!-- DPCR Information -->
        <el-card shadow="never" class="mb-4">
          <template #header>
            <div class="flex items-center">
              <el-icon class="mr-2"><Document /></el-icon>
              <span>DPCR Information</span>
            </div>
          </template>
          
          <el-row :gutter="16">
            <el-col :span="12">
              <div class="info-item">
                <label>Division:</label>
                <span>{{ dpcrInfo.division }}</span>
              </div>
            </el-col>
            <el-col :span="12">
              <div class="info-item">
                <label>Year:</label>
                <span>{{ dpcrInfo.year }}</span>
              </div>
            </el-col>
          </el-row>

          <el-row :gutter="16">
            <el-col :span="6">
              <div class="info-item">
                <label>Period From:</label>
                <span>{{ dpcrInfo.period_start || dpcrInfo.month_from || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Period To:</label>
                <span>{{ dpcrInfo.period_end || dpcrInfo.month_to || 'N/A' }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Department Head List -->
        <el-card shadow="never">
          <template #header>
            <div class="flex justify-between items-center">
              <div class="flex items-center">
                <el-icon class="mr-2"><User /></el-icon>
                <span>Department Head Performance Ratings</span>
              </div>
              <el-tag type="info">{{ departmentHeads.length }} department heads</el-tag>
            </div>
          </template>

          <div class="mb-4">
            <el-row :gutter="16" align="middle">
              <el-col :span="12">
                <el-input
                  v-model="searchQuery"
                  placeholder="Search department heads..."
                  :prefix-icon="Search"
                  clearable
                />
              </el-col>
            </el-row>
          </div>

          <el-table 
            :data="filteredDepartmentHeads" 
            border 
            stripe
            :height="tableHeight"
            class="rating-table"
          >
            <el-table-column label="Department Head" min-width="260">
              <template #default="{ row }">
                <div class="position-info">
                  <div class="position-name">{{ fullName(row) }}</div>
                  <div class="employee-name text-muted">
                    {{ row.employee_no }} | {{ row.position }}
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column prop="recalibration_overall" label="Status" width="180">
              <template #default="{ row }">
                <el-tag type="info">{{ row.recalibration_overall || 'Pending' }}</el-tag>
              </template>
            </el-table-column>

            <el-table-column label="PMT" width="120">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row, 'pmt')">
                  {{ levelStatus(row, 'pmt') }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Action" width="140">
              <template #default="{ row }">
                <el-button type="primary" plain size="small" @click="openPreview(row)">
                  View
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <el-empty 
            v-if="!loading && filteredDepartmentHeads.length === 0"
            description="No department heads found for this DPCR"
          />
        </el-card>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="visible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- Preview Drawer -->
  <el-drawer v-model="previewVisible" title="DPCR Preview" size="60%">
    <div v-if="selectedRow">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="Department Head">{{ fullName(selectedRow) }}</el-descriptions-item>
        <el-descriptions-item label="Employee No.">{{ selectedRow.employee_no || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Position">{{ selectedRow.position || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Status">{{ selectedRow.status_name || 'No status' }}</el-descriptions-item>
        <el-descriptions-item label="Period From">{{ selectedRow.period_start || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Period To">{{ selectedRow.period_end || 'N/A' }}</el-descriptions-item>
      </el-descriptions>

      <h4 class="mt-4 mb-2">Outputs</h4>
      <el-table :data="selectedRow.outputs || []" size="small" border>
        <el-table-column prop="Outputs" label="Output" min-width="200" />
        <el-table-column prop="Target_measures" label="Target Measures" min-width="200" />
        <el-table-column prop="actual_accomplishments" label="Accomplishment" min-width="200" />
        <el-table-column prop="quality_rating" label="Quality" width="90" />
        <el-table-column prop="efficiency_rating" label="Efficiency" width="100" />
        <el-table-column prop="effectiveness_rating" label="Effectiveness" width="110" />
        <el-table-column prop="average_rating" label="Average" width="90" />
        <el-table-column prop="remarks" label="Remarks" min-width="160" />
      </el-table>

      <h4 class="mt-4 mb-2">Recalibrations</h4>
      <div v-for="level in recalibrationLevels" :key="level.key" class="mb-4">
        <div class="flex justify-between items-center mb-2">
          <span class="font-medium text-base">{{ level.label }}</span>
          <el-tag size="small" type="info">{{ groupedRecalibrations(selectedRow)[level.key]?.length || 0 }} entries</el-tag>
        </div>
        <el-table :data="groupedRecalibrations(selectedRow)[level.key] || []" size="small" border>
          <el-table-column prop="quality_rating" label="Quality" width="90" />
          <el-table-column prop="efficiency_rating" label="Efficiency" width="100" />
          <el-table-column prop="effectiveness_rating" label="Effectiveness" width="110" />
          <el-table-column prop="average_rating" label="Average" width="90" />
          <el-table-column prop="remarks" label="Remarks" min-width="160" />
          <el-table-column prop="status_name" label="Status" width="140">
            <template #default="{ row }">
              {{ row.status_name || row.status || 'Pending' }}
            </template>
          </el-table-column>
        </el-table>
        <el-empty v-if="(groupedRecalibrations(selectedRow)[level.key] || []).length === 0" description="No entries" />
      </div>
    </div>
    <template #footer>
      <div style="text-align: right; width: 100%;">
        <el-button @click="previewVisible = false">Close</el-button>
      </div>
    </template>
  </el-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Document, User, Search } from '@element-plus/icons-vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  reviewData: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'save-ratings', 'get-adjectival-rating'])

const searchQuery = ref('')
const departmentHeads = ref([])
const tableHeight = ref('400px')
const previewVisible = ref(false)
const selectedRow = ref(null)
// DPCR calibration is PMT-only
const recalibrationLevels = [
  { key: 'pmt', label: 'PMT Calibration' }
]

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const dpcrInfo = computed(() => {
  return props.reviewData.length > 0 ? props.reviewData[0] : null
})

const title = computed(() => {
  const info = dpcrInfo.value
  if (info) {
    return `Department Head Review - ${info.division || 'N/A'}`
  }
  return 'Department Head Performance Review'
})

const filteredDepartmentHeads = computed(() => {
  if (!searchQuery.value) return departmentHeads.value
  
  const query = searchQuery.value.toLowerCase()
  return departmentHeads.value.filter(emp => 
    (emp.first_name || '').toLowerCase().includes(query) ||
    (emp.last_name || '').toLowerCase().includes(query) ||
    (emp.employee_no || '').toLowerCase().includes(query) ||
    (emp.position || '').toLowerCase().includes(query)
  )
})

const fullName = (row) => `${row.first_name || ''} ${row.middle_name || ''} ${row.last_name || ''}`.trim()

const levelStatus = (row, level) => {
  if (!row?.recalibration_levels) return 'Pending'
  return row.recalibration_levels[level] ? row.recalibration_levels[level].toString().toUpperCase() : 'Pending'
}

const statusTagType = (row, level) => {
  const status = levelStatus(row, level).toLowerCase()
  if (status.includes('approved') || status.includes('completed')) return 'success'
  if (status.includes('pending')) return 'info'
  if (status.includes('draft')) return 'info'
  if (status.includes('rejected')) return 'danger'
  return 'warning'
}

const getRecalibrations = (row) => {
  // DPCR recalibrations are stored per employee_dpcr_id (not per output),
  // so we display them as an employee-level list.
  if (!row) return []
  const order = { pmt: 1 }
  return (row.recalibrations || [])
    .map((rec) => ({ ...rec, _order: order[rec.recalibration_level] || 99 }))
    .sort((a, b) => a._order - b._order || a.id - b.id)
}

const groupedRecalibrations = (row) => {
  const grouped = { pmt: [] }
  getRecalibrations(row).forEach((rec) => {
    const key = rec.recalibration_level
    if (grouped[key]) {
      grouped[key].push(rec)
    }
  })
  return grouped
}

watch(() => props.reviewData, (newData) => {
  if (newData && newData.length > 0) {
    departmentHeads.value = newData.map(emp => ({
      ...emp,
      period_start: emp.period_start || emp.month_from || '',
      period_end: emp.period_end || emp.month_to || ''
    }))
  }
}, { immediate: true })

const openPreview = (row) => {
  selectedRow.value = row
  previewVisible.value = true
}
</script>

<style scoped>
.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item label {
  font-weight: 500;
  color: #606266;
  font-size: 0.875rem;
}

.info-item span {
  color: #303133;
  font-weight: 500;
}

.rating-table {
  --el-table-border-color: #e4e7ed;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.position-info {
  display: flex;
  flex-direction: column;
}

.position-name {
  font-weight: 600;
  color: #303133;
  margin-bottom: 2px;
}

.text-muted {
  color: #909399;
}
</style>
