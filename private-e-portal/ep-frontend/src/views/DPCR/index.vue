<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div v-if="accessLoaded && canViewDpcr" class="dpcr-layout">
      <div class="dpcr-left-panel">
        <el-card shadow="hover" class="mb-4">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">DPCR Records</h3>
              <el-button v-if="canViewDpcr" type="primary" size="small" @click="newDPCR">
                <el-icon class="mr-1"><Plus /></el-icon>
                New DPCR
              </el-button>
            </div>
          </template>

          <div class="mb-4">
            <el-input
              v-model="searchQuery"
              placeholder="Search DPCR records..."
              size="small"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <div class="dpcr-list">
            <div
              v-for="record in filteredRecords"
              :key="record.id"
              class="dpcr-list-item"
              :class="{ active: selectedRecord?.id === record.id }"
              @click="selectRecord(record)"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="font-medium text-slate-900">{{ record.period || 'No Period' }}</div>
                  <div class="text-sm text-slate-600">{{ record.division || 'No Division' }}</div>
                </div>
                <div class="flex items-center gap-2">
                  <el-button
                    v-if="canViewDpcr && isPMT && record?.id"
                    size="small"
                    type="primary"
                    plain
                    @click.stop="calibrateFromList(record)"
                  >
                    Calibrate (PMT)
                  </el-button>
                  <el-icon class="text-slate-400">
                    <ArrowRight />
                  </el-icon>
                </div>
              </div>
            </div>

            <div v-if="filteredRecords.length === 0" class="text-center py-8 text-slate-500">
              <el-icon size="48" class="mb-2"><Document /></el-icon>
              <p>No DPCR records found</p>
              <p class="text-sm mt-2">Click "New DPCR" to create one</p>
            </div>
          </div>
        </el-card>

        <!-- Info Card -->
        <el-card shadow="hover">
          <template #header>
            <h3 class="text-lg font-semibold text-slate-900">Information</h3>
          </template>
          <div class="text-sm text-slate-600 space-y-2">
            <p><strong>Legend:</strong></p>
            <p>Q - Quality</p>
            <p>E - Efficiency</p>
            <p>T - Timeliness</p>
            <p>A - Average</p>
            <div class="mt-2">
              <p class="font-medium text-slate-700">Adjectival Ratings:</p>
              <ul class="list-disc ml-5 mt-1 space-y-1">
                <li>5 - Outstanding</li>
                <li>4 - Very Satisfactory</li>
                <li>3 - Satisfactory</li>
                <li>2 - Unsatisfactory</li>
              </ul>
              <p class="mt-2 text-slate-500">Note: Q, E, T, and A accept ratings 2 to 5 only.</p>
            </div>
          </div>
        </el-card>
      </div>

      <div class="dpcr-right-panel">
        <el-card shadow="hover" class="dpcr-form-card">
          <template #header>
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-slate-900">
                {{ selectedRecord ? 'Edit DPCR' : 'New DPCR Form' }}
              </h3>
              <div class="flex items-center gap-2">
                <el-button v-if="canViewDpcr && selectedRecord?.id" type="info" size="small" @click="printDPCR(selectedRecord.id)">
                  Preview
                </el-button>
                <el-button type="success" size="small" :loading="submitting" @click="submitForm">
                  {{ selectedRecord ? 'Update' : 'Submit' }}
                </el-button>
                <el-button text @click="closeForm" class="close-btn">
                  <el-icon><Close /></el-icon>
                </el-button>
              </div>
            </div>
          </template>

          <el-form :model="form" label-position="top" ref="formRef" class="dpcr-form">
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Division">
                  <el-input v-model="form.department" disabled />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Period">
                  <el-date-picker
                    v-model="form.period"
                    type="daterange"
                    range-separator="to"
                    start-placeholder="Start date"
                    end-placeholder="End date"
                    format="YYYY-MM-DD"
                    value-format="YYYY-MM-DD"
                    :editable="false"
                    unlink-panels
                    @change="handlePeriodChange"
                    style="width: 100%"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-divider content-position="center">Review &amp; Approval</el-divider>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Approved by">
                  <el-select v-model="form.approvedByEmployeeId" filterable style="width: 100%" disabled>
                    <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker v-model="form.approvedDate" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
                </el-form-item>
              </el-col>
            </el-row>

            <template v-for="section in outputSections" :key="section.key">
              <el-divider>{{ section.title }}</el-divider>
              <div class="table-container">
                <el-table :data="form[section.key]" border size="small" style="width: 100%">
                  <el-table-column prop="outputs" label="Outputs" min-width="140">
                    <template #default="scope">
                      <el-input v-model="scope.row.outputs" size="small" :placeholder="section.placeholder" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column prop="targetMeasures" label="Target Measures" min-width="160">
                    <template #default="scope">
                      <el-input v-model="scope.row.targetMeasures" size="small" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column prop="actualAccomplishments" label="Actual Accomplishments" min-width="150">
                    <template #default="scope">
                      <el-input v-model="scope.row.actualAccomplishments" size="small" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column label="Q" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.q" size="small" @input="onRatingInput(scope.row, 'q')" placeholder="2-5" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column label="E" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.e" size="small" @input="onRatingInput(scope.row, 'e')" placeholder="2-5" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column label="T" width="70">
                    <template #default="scope">
                      <el-input v-model="scope.row.t" size="small" @input="onRatingInput(scope.row, 't')" placeholder="2-5" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column label="A" width="80">
                    <template #default="scope">
                      <el-input v-model="scope.row.a" size="small" disabled />
                    </template>
                  </el-table-column>
                  <el-table-column prop="remarks" label="Remarks" min-width="120">
                    <template #default="scope">
                      <el-input v-model="scope.row.remarks" size="small" :disabled="recalibrationMode" />
                    </template>
                  </el-table-column>
                  <el-table-column label="Action" width="70" fixed="right">
                    <template #default="scope">
                      <el-button @click="removeRow(section.key, scope.$index)" type="danger" size="small" text :disabled="recalibrationMode">Delete</el-button>
                    </template>
                  </el-table-column>
                </el-table>
              </div>
              <el-button type="primary" @click="addRow(section.key)" size="small" class="add-row" :disabled="recalibrationMode">
                <el-icon class="mr-1"><Plus /></el-icon>
                Add {{ section.addLabel }}
              </el-button>
            </template>

            <el-divider>Signatories</el-divider>
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Discussed with">
                  <el-select v-model="form.planningOfficerEmployeeId" filterable style="width: 100%" @change="onPlanningOfficerChange">
                    <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker v-model="form.planningOfficerDate" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Assessed by (Supervisor)">
                  <el-select v-model="form.assessedByEmployeeId" filterable style="width: 100%" disabled>
                    <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker v-model="form.assessedDate" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Final Rating by">
                  <el-select v-model="form.finalRaterEmployeeId" filterable style="width: 100%">
                    <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Date">
                  <el-date-picker v-model="form.finalRateDate" type="date" value-format="YYYY-MM-DD" style="width: 100%" />
                </el-form-item>
              </el-col>
            </el-row>

            <el-divider v-if="isPMT && selectedRecord" ref="recalibrationSection">PMT Recalibration</el-divider>
            <div v-if="isPMT && selectedRecord" class="table-container">
              <el-table :data="recalibrationOutputs" border size="small" style="width: 100%">
                <el-table-column prop="outputs" label="Outputs" min-width="140" />
                <el-table-column label="Original (Division Chief)" min-width="320">
                  <template #default="scope">
                    <div class="flex gap-2">
                      <span class="text-xs text-slate-500">Q:</span><span class="text-xs">{{ scope.row.original.q }}</span>
                      <span class="text-xs text-slate-500">E:</span><span class="text-xs">{{ scope.row.original.e }}</span>
                      <span class="text-xs text-slate-500">T:</span><span class="text-xs">{{ scope.row.original.t }}</span>
                      <span class="text-xs text-slate-500">A:</span><span class="text-xs">{{ scope.row.original.a }}</span>
                    </div>
                  </template>
                </el-table-column>
                <el-table-column label="Calibration Input" min-width="420">
                  <template #default="scope">
                    <div class="flex items-center gap-2">
                      <el-input v-model="scope.row.pmt.q" size="small" style="width: 64px" @input="onRecalRatingInput(scope.row.pmt, 'q')" placeholder="2-5" />
                      <el-input v-model="scope.row.pmt.e" size="small" style="width: 64px" @input="onRecalRatingInput(scope.row.pmt, 'e')" placeholder="2-5" />
                      <el-input v-model="scope.row.pmt.t" size="small" style="width: 64px" @input="onRecalRatingInput(scope.row.pmt, 't')" placeholder="2-5" />
                      <el-input v-model="scope.row.pmt.a" size="small" style="width: 74px" disabled />
                      <el-input v-model="scope.row.pmt.remarks" size="small" placeholder="Remarks" />
                    </div>
                  </template>
                </el-table-column>
              </el-table>

              <div class="form-actions">
                <el-button type="primary" :loading="recalSubmitting" @click="submitRecalibration('pmt')">
                  Save PMT Recalibration
                </el-button>
              </div>
            </div>

            <div class="form-actions">
              <el-button type="success" :loading="submitting" @click="submitForm">
                {{ selectedRecord ? 'Update' : 'Submit' }}
              </el-button>
              <el-button :disabled="submitting" @click="resetForm">Reset</el-button>
            </div>
          </el-form>
        </el-card>

        <!-- Print Preview Dialog -->
        <el-dialog v-model="previewVisible" title="DPCR Print Preview" width="80%" :close-on-click-modal="false" @close="closePreview">
          <div v-if="previewLoading" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-slate-600">Generating PDF...</span>
          </div>
          <div v-else style="height:80vh;">
            <iframe :src="previewUrl" ref="previewFrame" style="width:100%;height:100%;border:0;"></iframe>
          </div>
          <template #footer>
            <div class="flex items-center justify-between w-full">
              <div class="text-sm text-slate-500">PDF generated from current DPCR entry</div>
              <div>
                <el-button @click="downloadPreview" :disabled="!previewUrl">Download</el-button>
                <el-button type="primary" @click="printPreview" :disabled="!previewUrl">Print</el-button>
                <el-button @click="closePreview">Close</el-button>
              </div>
            </div>
          </template>
        </el-dialog>
      </div>
    </div>
    <div v-else-if="!accessLoaded" class="p-6 text-center text-slate-600">
      Loading access...
    </div>
    <div v-else class="p-6 text-center text-slate-600">
      DPCR is not available. It will appear when there is an ongoing DPCR you need to complete.
    </div>
  </MainLayout>
</template>

<script>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { ElNotification, ElMessage } from 'element-plus'
import { Search, Document, Plus, ArrowRight, Close } from '@element-plus/icons-vue'
import MainLayout from '../../layout/MainLayout.vue'

export default {
  name: 'DPCRView',
  components: { MainLayout, Search, Document, Plus, ArrowRight, Close },
  setup() {
    const hasPortalAccess = ref(false)
    const accessLoaded = ref(false)
    const canViewDpcr = computed(() => hasPortalAccess.value)
    const dpcrRecords = ref([])
    const loading = ref(false)
    const submitting = ref(false)
    const searchQuery = ref('')
    const selectedRecord = ref(null)
    const formRef = ref(null)
    const employees = ref([])
    const fixedStart = ref(null)
    const isPMT = ref(false)
    const isHR = ref(false)
    const recalSubmitting = ref(false)
    const recalibrationOutputs = ref([])
    const activeRecalLevel = ref(null)
    const recalibrationMode = ref(false)
    const recalibrationSection = ref(null)
    const previewVisible = ref(false)
    const previewUrl = ref('')
    const previewLoading = ref(false)
    const previewFrame = ref(null)

    const outputSections = [
      { key: 'coreOutputs', title: 'Core Functions', placeholder: 'Core Function', addLabel: 'Core Row' },
      { key: 'strategicOutputs', title: 'Strategic Priority', placeholder: 'Strategic Output', addLabel: 'Strategic Row' },
      { key: 'supportOutputs', title: 'Support Functions', placeholder: 'Support Function', addLabel: 'Support Row' }
    ]

    const defaultRow = () => ({
      outputs: '',
      targetMeasures: '',
      actualAccomplishments: '',
      q: '',
      e: '',
      t: '',
      a: '',
      remarks: ''
    })

    const form = reactive({
      id: 0,
      departmentId: null,
      department: '',
      period: [],
      strategicOutputs: [defaultRow()],
      coreOutputs: [defaultRow()],
      supportOutputs: [defaultRow()],
      planningOfficerEmployeeId: null,
      planningOfficerDate: null,
      approvedByEmployeeId: null,
      approvedDate: null,
      assessedByEmployeeId: null,
      assessedDate: null,
      finalRaterEmployeeId: null,
      finalRateDate: null
    })

    const breadcrumbs = [
      { name: 'Dashboard', path: '/dashboard' },
      { name: 'DPCR', path: '/dpcr' }
    ]

    const filteredRecords = computed(() => {
      if (!searchQuery.value) return dpcrRecords.value
      const q = searchQuery.value.toLowerCase()
      return dpcrRecords.value.filter(r =>
        (r.period || '').toLowerCase().includes(q) ||
        (r.division || '').toLowerCase().includes(q)
      )
    })

    async function loadDPCRRecords() {
      try {
        loading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const response = await ApiService.getEmployeeDPCRList()
        if (response && response.success) {
          dpcrRecords.value = response.data || []
        } else {
          ElNotification({
            title: 'Error',
            message: response?.message || 'Failed to load DPCR records.',
            type: 'error',
            duration: 6000
          })
        }
      } catch (e) {
        ElNotification({
          title: 'Error',
          message: e?.message || 'Failed to load DPCR records.',
          type: 'error',
          duration: 6000
        })
      } finally {
        loading.value = false
      }
    }

    function collectRatingValues(q, e, t) {
      return [q, e, t]
        .map(v => Number(v))
        .filter(v => Number.isFinite(v) && v >= 2 && v <= 5)
    }

    function normalizeRatingForDisplay(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return ''
      return n
    }

    function normalizeAverageForDisplay(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return ''
      return Number.isInteger(n) ? String(n) : n.toFixed(2)
    }

    function computeAverage(q, e, t) {
      const values = collectRatingValues(q, e, t)
      if (!values.length) return ''
      const avg = values.reduce((sum, v) => sum + v, 0) / values.length
      const clamped = Math.max(2, Math.min(5, avg))
      return clamped.toFixed(2)
    }

    function ratingForPayload(v) {
      const n = Number(v)
      if (!Number.isFinite(n) || n < 2 || n > 5) return null
      return n
    }

    function averageRatingForPayload(q, e, t, fallback = null) {
      const values = [q, e, t].filter(v => v !== null)
      if (values.length) {
        const avg = values.reduce((sum, v) => sum + v, 0) / values.length
        return Math.round(Math.max(2, Math.min(5, avg)) * 100) / 100
      }
      const fb = Number(fallback)
      return Number.isFinite(fb) && fb >= 2 ? Math.round(fb * 100) / 100 : null
    }

    function onRatingInput(row, key) {
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '' || v === '0' || v === '1') {
        row[key] = ''
        row.a = computeAverage(row.q, row.e, row.t)
        return
      }
      const n = Math.max(2, Math.min(5, parseInt(v, 10)))
      row[key] = n
      row.a = computeAverage(row.q, row.e, row.t)
    }

    function mapOutputFromApi(o) {
      const q = normalizeRatingForDisplay(o.q)
      const e = normalizeRatingForDisplay(o.e)
      const t = normalizeRatingForDisplay(o.t)
      return {
        id: o.id != null ? Number(o.id) : undefined,
        outputs: o.outputs ?? '',
        targetMeasures: o.targetMeasures ?? '',
        actualAccomplishments: o.actualAccomplishments ?? '',
        q,
        e,
        t,
        a: normalizeAverageForDisplay(o.a) || computeAverage(q, e, t),
        remarks: o.remarks ?? ''
      }
    }

    function applyOutputsToForm(outputs) {
      const strategic = []
      const core = []
      const support = []

      for (const o of outputs || []) {
        const type = String(o.functionType || o.function_type || 'core').toLowerCase().trim()
        const row = mapOutputFromApi(o)
        if (type === 'strategic') {
          strategic.push(row)
        } else if (type === 'support') {
          support.push(row)
        } else {
          core.push(row)
        }
      }

      form.strategicOutputs = strategic.length ? strategic : [defaultRow()]
      form.coreOutputs = core.length ? core : [defaultRow()]
      form.supportOutputs = support.length ? support : [defaultRow()]
    }

    function getAllFormOutputs() {
      return [...form.coreOutputs, ...form.strategicOutputs, ...form.supportOutputs]
    }

    function rowHasContent(row) {
      return [
        row.outputs,
        row.targetMeasures,
        row.actualAccomplishments
      ].some(v => String(v ?? '').trim() !== '')
    }

    function mergeOutputsForPayload() {
      return [
        ...form.coreOutputs.map(o => ({ ...o, functionType: 'core', function_type: 'core' })),
        ...form.strategicOutputs.map(o => ({ ...o, functionType: 'strategic', function_type: 'strategic' })),
        ...form.supportOutputs.map(o => ({ ...o, functionType: 'support', function_type: 'support' }))
      ].filter(rowHasContent)
    }

    function addRow(sectionKey) {
      form[sectionKey].push(defaultRow())
    }

    function removeRow(sectionKey, idx) {
      if (form[sectionKey].length > 1) form[sectionKey].splice(idx, 1)
    }

    function todayDateString() {
      return new Date().toISOString().slice(0, 10)
    }

    function ensureSignatoryDates() {
      const today = todayDateString()
      if (!form.planningOfficerDate) form.planningOfficerDate = today
      if (!form.approvedDate) form.approvedDate = today
      if (!form.assessedDate) form.assessedDate = today
      if (!form.finalRateDate) form.finalRateDate = today
    }

    async function loadFormData() {
      const ApiService = (await import('../../services/api.js')).default
      const response = await ApiService.getEmployeeDPCRFormData()
      if (response && response.success) {
        const data = response.data
        employees.value = (data.employees || []).map(e => ({
          ...e,
          id: e?.id != null ? Number(e.id) : e.id
        }))
        form.departmentId = data.divisionId || data.departmentId || null
        form.department = data.division || data.department || ''
        // default period from header month/year (optional) not converting to dates here
        if (data.pmt_member && data.pmt_member.id) {
          form.assessedByEmployeeId = Number(data.pmt_member.id)
        }
        if (data.agency_head && data.agency_head.id) {
          form.approvedByEmployeeId = Number(data.agency_head.id)
        }
        if (!form.approvedDate) form.approvedDate = todayDateString()
      }
    }

    async function loadUserAccess() {
      try {
        const ApiService = (await import('../../services/api.js')).default
        const res = await ApiService.checkDPCRAccess()
        if (res && res.success) {
          hasPortalAccess.value = !!res.data?.has_access
          isHR.value = !!res.data?.is_hr
          isPMT.value = !!res.data?.is_pmt
        } else {
          hasPortalAccess.value = false
        }
      } catch (e) {
        hasPortalAccess.value = false
      } finally {
        accessLoaded.value = true
      }
    }

    async function loadDPCRRecord(id) {
      const ApiService = (await import('../../services/api.js')).default
      const res = await ApiService.getEmployeeDPCR(id)
      if (res && res.success) {
        const d = res.data
        await loadFormData()
        form.id = d.id || id
        form.departmentId = d.divisionId || d.departmentId || form.departmentId
        form.department = d.division || d.department || form.department
        form.period = Array.isArray(d.period) ? d.period : []

        form.planningOfficerEmployeeId = d.planningOfficerEmployeeId != null ? Number(d.planningOfficerEmployeeId) : null
        form.planningOfficerDate = d.planningOfficerDate ?? null
        form.approvedByEmployeeId = d.approvedByEmployeeId != null ? Number(d.approvedByEmployeeId) : form.approvedByEmployeeId
        form.approvedDate = d.approvedDate ?? null
        form.assessedByEmployeeId = d.assessedByEmployeeId != null ? Number(d.assessedByEmployeeId) : form.assessedByEmployeeId
        form.assessedDate = d.assessedDate ?? null
        form.finalRaterEmployeeId = d.finalRaterEmployeeId != null ? Number(d.finalRaterEmployeeId) : null
        form.finalRateDate = d.finalRateDate ?? null

        if (Array.isArray(d.outputs) && d.outputs.length) {
          applyOutputsToForm(d.outputs)
        } else {
          form.strategicOutputs = [defaultRow()]
          form.coreOutputs = [defaultRow()]
          form.supportOutputs = [defaultRow()]
        }
        if (typeof d.is_pmt !== 'undefined') isPMT.value = !!d.is_pmt
        if (typeof d.is_hr !== 'undefined') isHR.value = !!d.is_hr
        // Build recalibration rows (separate from original)
        recalibrationOutputs.value = (d.outputs || []).map(o => ({
          id: o.id != null ? Number(o.id) : undefined,
          outputs: o.outputs ?? '',
          original: {
            q: o.q ?? '',
            e: o.e ?? '',
            t: o.t ?? '',
            a: o.a ?? ''
          },
          hr: {
            q: o.hr_recalibration?.q ?? '',
            e: o.hr_recalibration?.e ?? '',
            t: o.hr_recalibration?.t ?? '',
            a: o.hr_recalibration?.a ?? '',
            remarks: o.hr_recalibration?.remarks ?? ''
          },
          pmt: {
            q: o.pmt_recalibration?.q ?? '',
            e: o.pmt_recalibration?.e ?? '',
            t: o.pmt_recalibration?.t ?? '',
            a: o.pmt_recalibration?.a ?? '',
            remarks: o.pmt_recalibration?.remarks ?? ''
          }
        }))
        fixedStart.value = null
      } else {
        ElNotification({
          title: 'Error',
          message: res?.message || 'Failed to load DPCR record.',
          type: 'error',
          duration: 6000
        })
      }
    }

    async function selectRecord(record) {
      selectedRecord.value = record
      await loadDPCRRecord(record.id)
    }

    async function calibrateFromList(record) {
      if (!record?.id) return
      selectedRecord.value = record
      activeRecalLevel.value = 'pmt'
      recalibrationMode.value = true
      await loadDPCRRecord(record.id)
      await nextTick()
      // Scroll user to the recalibration section
      const el = recalibrationSection.value?.$el || recalibrationSection.value
      if (el && typeof el.scrollIntoView === 'function') {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' })
      }
    }

    function resetForm() {
      form.id = 0
      form.period = []
      form.strategicOutputs = [defaultRow()]
      form.coreOutputs = [defaultRow()]
      form.supportOutputs = [defaultRow()]
      form.planningOfficerEmployeeId = null
      form.planningOfficerDate = null
      form.approvedByEmployeeId = null
      form.approvedDate = null
      form.assessedDate = null
      form.finalRaterEmployeeId = null
      form.finalRateDate = null
    }

    function newDPCR() {
      selectedRecord.value = null
      resetForm()
      recalibrationMode.value = false
      loadFormData()
    }

    function closeForm() {
      selectedRecord.value = null
      resetForm()
      recalibrationMode.value = false
    }

    function onPlanningOfficerChange() {}

    function handlePeriodChange(val) {
      // Match OPCR behavior: keep first selected start date fixed.
      if (!val || val.length < 2) {
        fixedStart.value = null
        return
      }
      if (!fixedStart.value) {
        fixedStart.value = val[0]
        return
      }
      if (val[0] !== fixedStart.value) {
        form.period = [fixedStart.value, val[1]]
      }
    }

    function onRecalRatingInput(row, key) {
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '' || v === '0' || v === '1') {
        row[key] = ''
        row.a = computeAverage(row.q, row.e, row.t)
        return
      }
      const n = Math.max(2, Math.min(5, parseInt(v, 10)))
      row[key] = n
      row.a = computeAverage(row.q, row.e, row.t)
    }

    async function submitRecalibration(level) {
      if (!selectedRecord.value?.id) return
      try {
        recalSubmitting.value = true
        const ApiService = (await import('../../services/api.js')).default
        const payload = {
          outputs: recalibrationOutputs.value.map(r => {
            const target = level === 'hr' ? r.hr : r.pmt
            const q = ratingForPayload(target.q)
            const e = ratingForPayload(target.e)
            const t = ratingForPayload(target.t)
            return {
              id: r.id,
              q,
              e,
              t,
              a: averageRatingForPayload(q, e, t, target.a),
              remarks: target.remarks || ''
            }
          })
        }
        const res = await ApiService.saveDPCRRecalibration(selectedRecord.value.id, level, payload)
        if (res && res.success) {
          ElMessage.success(`${level.toUpperCase()} recalibration saved successfully!`)
          await loadDPCRRecord(selectedRecord.value.id)
        } else {
          ElNotification({ title: 'Error', message: res?.message || `Failed to save ${level.toUpperCase()} recalibration.`, type: 'error', duration: 6000 })
        }
      } catch (e) {
        ElNotification({ title: 'Error', message: e?.message || `Failed to save ${level.toUpperCase()} recalibration.`, type: 'error', duration: 6000 })
      } finally {
        recalSubmitting.value = false
      }
    }

    async function submitForm() {
      try {
        submitting.value = true
        ensureSignatoryDates()
        const outputRows = mergeOutputsForPayload()
        if (!outputRows.length) {
          ElNotification({
            title: 'Validation',
            message: 'Add at least one output row in Strategic, Core, or Support functions.',
            type: 'warning',
            duration: 6000
          })
          return
        }
        const ApiService = (await import('../../services/api.js')).default
        const payload = {
          id: form.id || 0,
          departmentId: form.departmentId,
          period: form.period,
          planningOfficerEmployeeId: form.planningOfficerEmployeeId,
          planningOfficerDate: form.planningOfficerDate,
          approvedByEmployeeId: form.approvedByEmployeeId,
          approvedDate: form.approvedDate,
          assessedByEmployeeId: form.assessedByEmployeeId,
          assessedDate: form.assessedDate,
          finalRaterEmployeeId: form.finalRaterEmployeeId,
          finalRateDate: form.finalRateDate,
          outputs: outputRows.map(o => {
            const q = ratingForPayload(o.q)
            const e = ratingForPayload(o.e)
            const t = ratingForPayload(o.t)
            return {
              outputs: o.outputs ?? '',
              targetMeasures: o.targetMeasures ?? '',
              actualAccomplishments: o.actualAccomplishments ?? '',
              functionType: o.functionType,
              function_type: o.function_type || o.functionType,
              q,
              e,
              t,
              a: averageRatingForPayload(q, e, t, o.a),
              remarks: o.remarks ?? ''
            }
          })
        }
        const response = await ApiService.saveEmployeeDPCR(payload)
        if (response && response.success) {
          const savedId = Number(response.data?.id || form.id || 0)
          if (savedId) {
            form.id = savedId
            selectedRecord.value = {
              ...(selectedRecord.value || {}),
              id: savedId,
              division: form.department,
              period: Array.isArray(form.period) && form.period.length >= 2
                ? `${form.period[0]} - ${form.period[1]}`
                : (selectedRecord.value?.period || '')
            }
            await loadDPCRRecord(savedId)
          }
          ElMessage.success('DPCR saved successfully!')
          await loadDPCRRecords()
        } else {
          ElNotification({ title: 'Error', message: response?.message || 'Failed to save DPCR.', type: 'error', duration: 6000 })
        }
      } catch (e) {
        ElNotification({ title: 'Error', message: e?.message || 'Failed to save DPCR.', type: 'error', duration: 6000 })
      } finally {
        submitting.value = false
      }
    }

    async function printDPCR(id) {
      if (!id) return
      try {
        previewVisible.value = true
        previewLoading.value = true
        const ApiService = (await import('../../services/api.js')).default
        const blob = await ApiService.downloadEmployeeDPCRPDF(id)
        if (!blob) {
          previewLoading.value = false
          ElNotification({ title: 'Error', message: 'Failed to generate DPCR PDF. Please try again.', type: 'error', duration: 6000 })
          return
        }
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = URL.createObjectURL(blob)
        previewLoading.value = false
      } catch (e) {
        previewLoading.value = false
        ElNotification({ title: 'Error', message: e?.message || 'Failed to generate DPCR PDF.', type: 'error', duration: 6000 })
      }
    }

    function downloadPreview() {
      if (!previewUrl.value) return
      const a = document.createElement('a')
      a.href = previewUrl.value
      a.download = `dpcr_${selectedRecord.value?.id || 'preview'}.pdf`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    }

    function printPreview() {
      if (!previewFrame.value) return
      try {
        previewFrame.value.contentWindow?.focus()
        previewFrame.value.contentWindow?.print()
      } catch (e) {
        // ignore
      }
    }

    function closePreview() {
      previewVisible.value = false
      previewLoading.value = false
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = ''
      }
    }

    onMounted(async () => {
      await loadUserAccess()
      if (!hasPortalAccess.value) return
      await loadFormData()
      await loadDPCRRecords()
    })

    return {
      loading,
      submitting,
      dpcrRecords,
      searchQuery,
      filteredRecords,
      breadcrumbs,
      selectedRecord,
      selectRecord,
      form,
      formRef,
      outputSections,
      employees,
      onPlanningOfficerChange,
      handlePeriodChange,
      isPMT,
      isHR,
      canViewDpcr,
      accessLoaded,
      recalibrationMode,
      recalibrationSection,
      previewVisible,
      previewUrl,
      previewLoading,
      previewFrame,
      downloadPreview,
      printPreview,
      closePreview,
      recalSubmitting,
      recalibrationOutputs,
      onRecalRatingInput,
      submitRecalibration,
      calibrateFromList,
      activeRecalLevel,
      addRow,
      removeRow,
      onRatingInput,
      submitForm,
      printDPCR,
      resetForm,
      newDPCR,
      closeForm
    }
  }
}
</script>

<style scoped>
.dpcr-layout {
  display: flex;
  gap: 1.5rem;
  height: calc(100vh - 200px);
  min-height: 600px;
}
.dpcr-left-panel {
  flex: 0 0 400px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.dpcr-right-panel {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition: all 0.3s ease;
  max-width: 1200px;
}
.dpcr-form-card {
  flex: 1;
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.dpcr-form-card :deep(.el-card) {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.dpcr-form-card :deep(.el-card__header) {
  flex-shrink: 0;
}
.dpcr-form-card :deep(.el-card__body) {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  padding: 20px;
}
.dpcr-list-item {
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  background: #fff;
}
.dpcr-list-item.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.close-btn {
  padding: 6px 10px;
}

.add-row {
  margin-top: 10px;
}

.form-actions {
  margin-top: 16px;
  display: flex;
  gap: 10px;
  padding: 12px 0 0 0;
  border-top: 1px solid #f1f5f9;
}

@media (max-width: 1200px) {
  .dpcr-layout {
    flex-direction: column;
    height: auto;
    max-height: none;
  }

  .dpcr-left-panel {
    max-height: 400px;
  }

  .dpcr-right-panel {
    max-height: calc(100vh - 280px);
  }
}
</style>

