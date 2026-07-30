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
            <el-col :span="6">
              <div class="info-item">
                <label>Division:</label>
                <span>{{ dpcrInfo.division }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Section:</label>
                <span>{{ dpcrInfo.section }}</span>
              </div>
            </el-col>
            <el-col :span="6">
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
                <span>{{ dpcrInfo.period_start || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Period To:</label>
                <span>{{ dpcrInfo.period_end || 'N/A' }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>

        <!-- Department List -->
        <el-card shadow="never">
          <template #header>
            <div class="flex justify-between items-center">
              <div class="flex items-center">
                <el-icon class="mr-2"><OfficeBuilding /></el-icon>
                <span>Department Performance Ratings</span>
              </div>
              <div class="flex items-center gap-2">
                <el-button type="primary" size="small" @click="openSummary">
                  Summary of Ratings
                </el-button>
                <el-button type="success" size="small" @click="openPmt">
                  PMT Calibration Results
                </el-button>
                <el-tag type="info">{{ departments.length }} departments</el-tag>
              </div>
            </div>
          </template>

          <div class="mb-4">
            <el-row :gutter="16" align="middle">
              <el-col :span="12">
                <el-input
                  v-model="searchQuery"
                  placeholder="Search departments..."
                  :prefix-icon="Search"
                  clearable
                />
              </el-col>
            </el-row>
          </div>

          <el-table 
            :data="filteredDepartments" 
            border 
            stripe
            :height="tableHeight"
            class="rating-table"
          >
            <el-table-column label="Department" min-width="260">
              <template #default="{ row }">
                <div class="position-info">
                  <div class="position-name">{{ row.department || row.name }}</div>
                  <div class="employee-name text-muted">
                    {{ row.division || 'N/A' }}
                  </div>
                </div>
              </template>
            </el-table-column>

            <el-table-column prop="recalibration_overall" label="Status" width="180">
              <template #default="{ row }">
                <el-tag type="info">{{ row.recalibration_overall || 'Pending' }}</el-tag>
              </template>
            </el-table-column>

            <el-table-column label="Supervisor" width="140">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row, 'supervisor')">
                  {{ levelStatus(row, 'supervisor') }}
                </el-tag>
              </template>
            </el-table-column>

            <el-table-column label="HR" width="120">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row, 'hr')">
                  {{ levelStatus(row, 'hr') }}
                </el-tag>
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
            v-if="!loading && filteredDepartments.length === 0"
            description="No departments found for this DPCR"
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
        <el-descriptions-item label="Department">{{ selectedRow.department || selectedRow.name || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Division">{{ selectedRow.division || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Section">{{ selectedRow.section || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Status">{{ selectedRow.status_name || 'No status' }}</el-descriptions-item>
        <el-descriptions-item label="Period From">{{ selectedRow.period_start || 'N/A' }}</el-descriptions-item>
        <el-descriptions-item label="Period To">{{ selectedRow.period_end || 'N/A' }}</el-descriptions-item>
      </el-descriptions>

      <h4 class="mt-4 mb-2">Outputs</h4>
      <el-table :data="selectedRow.outputs || []" size="small" border>
        <el-table-column prop="output" label="Output" min-width="200" />
        <el-table-column prop="success_indicators" label="Success Indicators" min-width="200" />
        <el-table-column prop="accomplishment" label="Accomplishment" min-width="200" />
        <el-table-column prop="quality_rating" label="Quality" width="90" />
        <el-table-column prop="efficiency_rating" label="Efficiency" width="100" />
        <el-table-column prop="timeliness_rating" label="Timeliness" width="110" />
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
          <el-table-column prop="output" label="Output" min-width="200" />
          <el-table-column prop="quality_rating" label="Quality" width="90" />
          <el-table-column prop="efficiency_rating" label="Efficiency" width="100" />
          <el-table-column prop="timeliness_rating" label="Timeliness" width="110" />
          <el-table-column prop="average_rating" label="Average" width="90" />
          <el-table-column prop="status_name" label="Status" width="140">
            <template #default="{ row }">
              {{ row.status_name || row.status || 'Pending' }}
            </template>
          </el-table-column>
          <el-table-column prop="remarks" label="Remarks" min-width="160" />
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

  <!-- Summary of Ratings Modal -->
  <el-dialog
    v-model="summaryVisible"
    title="Summary of Ratings"
    width="80%"
    :close-on-click-modal="false"
    top="5vh"
  >
    <div v-if="summaryUrl" class="mb-3">
      <iframe :src="summaryUrl" style="width:100%; height:600px; border:1px solid #ddd;" frameborder="0"></iframe>
    </div>
    <el-empty v-else description="Generating summary..." />
    
    <template #footer>
      <div class="dialog-footer">
        <el-button type="success" :loading="summaryLoading" @click="downloadSummary">
          Download Summary
        </el-button>
        <el-button @click="summaryVisible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>

  <!-- PMT Calibration Modal -->
  <el-dialog
    v-model="pmtVisible"
    title="PMT Calibration Results"
    width="80%"
    :close-on-click-modal="false"
    top="5vh"
  >
    <div v-if="pmtUrl" class="mb-3">
      <iframe :src="pmtUrl" style="width:100%; height:600px; border:1px solid #ddd;" frameborder="0"></iframe>
    </div>
    <el-empty v-else description="Generating report..." />
    
    <template #footer>
      <div class="dialog-footer">
        <el-button type="success" :loading="pmtLoading" @click="downloadPmt">
          Download
        </el-button>
        <el-button @click="pmtVisible = false">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Document, OfficeBuilding, Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { dpcrApi } from '@/services/api'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  reviewData: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'save-ratings', 'get-adjectival-rating'])

// Reactive data
const searchQuery = ref('')
const departments = ref([])
const tableHeight = ref('400px')
const previewVisible = ref(false)
const selectedRow = ref(null)
const recalibrationLevels = [
  { key: 'supervisor', label: 'Supervisor Calibration' },
  { key: 'hr', label: 'HR Calibration' },
  { key: 'pmt', label: 'PMT Calibration' }
]
const summaryVisible = ref(false)
const summaryUrl = ref('')
const summaryLoading = ref(false)
const pmtVisible = ref(false)
const pmtUrl = ref('')
const pmtLoading = ref(false)

// Computed properties
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
    return `Department Review - ${info.division || 'N/A'}`
  }
  return 'Department Performance Review'
})

const filteredDepartments = computed(() => {
  if (!searchQuery.value) return departments.value
  
  const query = searchQuery.value.toLowerCase()
  return departments.value.filter(dept => 
    (dept.department || dept.name || '').toLowerCase().includes(query) ||
    (dept.division || '').toLowerCase().includes(query) ||
    (dept.section || '').toLowerCase().includes(query)
  )
})

const levelStatus = (row, level) => {
  if (!row?.recalibration_levels) return level === 'self' ? 'Pending' : 'Pending'
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
  if (!row || !row.outputs) return []
  const order = { supervisor: 1, hr: 2, pmt: 3 }
  return row.outputs.flatMap((output) =>
    (output.recalibrations || []).map((rec) => ({
      ...rec,
      output: output.output,
      _order: order[rec.recalibration_level] || 99
    }))
  ).sort((a, b) => a._order - b._order || a.id - b.id)
}

const groupedRecalibrations = (row) => {
  const grouped = { supervisor: [], hr: [], pmt: [] }
  getRecalibrations(row).forEach((rec) => {
    const key = rec.recalibration_level
    if (grouped[key]) {
      grouped[key].push(rec)
    }
  })
  return grouped
}

const openSummary = async () => {
  summaryVisible.value = true
  await downloadSummary()
}

const downloadSummary = async () => {
  const info = dpcrInfo.value
  const id = info?.dpcr_header_id || info?.id
  if (!id) {
    ElMessage.error('Missing DPCR reference')
    return
  }
  try {
    summaryLoading.value = true
    const res = await dpcrApi.summaryRatings(id)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    summaryUrl.value = url
  } catch (err) {
    console.error('Summary of ratings error', err)
    ElMessage.error('Failed to generate summary of ratings')
  } finally {
    summaryLoading.value = false
  }
}

const openPmt = async () => {
  pmtVisible.value = true
  await downloadPmt()
}

const downloadPmt = async () => {
  const info = dpcrInfo.value
  const id = info?.dpcr_header_id || info?.id
  if (!id) {
    ElMessage.error('Missing DPCR reference')
    return
  }
  try {
    pmtLoading.value = true
    const res = await dpcrApi.pmtCalibration(id)
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    pmtUrl.value = url
  } catch (err) {
    console.error('PMT calibration error', err)
    ElMessage.error('Failed to generate PMT calibration results')
  } finally {
    pmtLoading.value = false
  }
}

// Watchers
watch(() => props.reviewData, (newData) => {
  if (newData && newData.length > 0) {
    departments.value = newData.map(dept => ({
      ...dept,
      period_start: dept.period_start || dept.month_from || '',
      period_end: dept.period_end || dept.month_to || ''
    }))
  }
}, { immediate: true })

// Methods
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
