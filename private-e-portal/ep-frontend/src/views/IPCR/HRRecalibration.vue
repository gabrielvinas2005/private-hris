<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-slate-900 mb-2">IPCR HR Recalibration</h1>
        <p class="text-slate-600">
          Recalibrate IPCR records approved by the Head of Agency before PMT review
        </p>
      </div>

      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-slate-600">Loading HR recalibration queue...</span>
      </div>

      <div v-else-if="!isHR" class="bg-amber-50 border border-amber-200 rounded-lg p-6">
        <p class="text-amber-900">
          You do not have HR access for IPCR recalibration. HR access is required
          (<strong>with_hrm_access</strong> or admin).
        </p>
      </div>

      <div v-else>
        <el-card shadow="never">
          <el-tabs v-model="activeTab">
            <el-tab-pane :label="tabLabel('Pending', pendingRecords.length)" name="pending" />
            <el-tab-pane :label="tabLabel('Recalibrated', completedRecords.length)" name="completed" />
          </el-tabs>

          <el-table :data="currentRecords" stripe v-loading="tableLoading" :empty-text="emptyTableText" class="mt-2">
            <el-table-column prop="employee_name" label="Employee" min-width="200" />
            <el-table-column prop="period" label="Period" min-width="180" />
            <el-table-column label="Status" width="220">
              <template #default="{ row }">
                <el-tag :type="statusTagType(row)" size="small">{{ formatStatus(row.recalibration_status) }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column label="Approved by HoA" min-width="160">
              <template #default="{ row }">
                <span v-if="row.agency_head_approved_by_name">{{ row.agency_head_approved_by_name }}</span>
                <span v-else class="text-slate-400">—</span>
              </template>
            </el-table-column>
            <el-table-column label="HoA Approved" width="130">
              <template #default="{ row }">{{ formatDate(row.agency_head_approved_at) }}</template>
            </el-table-column>
            <el-table-column label="Last Updated" width="150">
              <template #default="{ row }">{{ formatDate(row.updated_at) }}</template>
            </el-table-column>
            <el-table-column label="Actions" width="160" fixed="right" align="center">
              <template #default="{ row }">
                <el-button type="primary" link :icon="View" @click="openRecalibration(row)">
                  {{ row.recalibration_status === 'agency_head_approved' ? 'Recalibrate' : 'View' }}
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </div>

      <el-dialog
        v-model="dialogVisible"
        title="IPCR HR Recalibration"
        width="95%"
        top="3vh"
        :close-on-click-modal="false"
      >
        <div v-if="dialogLoading" class="py-12 text-center text-slate-500">Loading IPCR details...</div>
        <div v-else-if="dialogData">
          <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div><span class="text-slate-500">Employee:</span> {{ dialogData.employee || '—' }}</div>
            <div><span class="text-slate-500">Period:</span> {{ formatPeriod(dialogData.period) }}</div>
            <div>
              <span class="text-slate-500">Status:</span>
              <el-tag :type="statusTagType(dialogData)" size="small" class="ml-1">
                {{ formatStatus(dialogData.recalibration_status) }}
              </el-tag>
            </div>
          </div>

          <el-alert
            v-if="dialogData.agency_head_approved_at || dialogData.recalibration_status === 'agency_head_approved'"
            type="success"
            :closable="false"
            show-icon
            class="mb-4"
          >
            <template #title>Approved by Head of Agency</template>
            <div class="text-sm">
              <span v-if="dialogData.agency_head_approved_by_name">
                <strong>Approved by:</strong> {{ dialogData.agency_head_approved_by_name }}
              </span>
              <span v-if="dialogData.agency_head_approved_at" class="ml-3">
                <strong>Date:</strong> {{ formatDate(dialogData.agency_head_approved_at) }}
              </span>
              <p v-if="dialogData.agency_head_approval_remarks" class="mt-1 mb-0">
                <strong>Remarks:</strong> {{ dialogData.agency_head_approval_remarks }}
              </p>
            </div>
          </el-alert>

          <p class="text-xs text-slate-500 mb-2">
            <strong>HoA Approved Average</strong> Q/E/T = (Employee + Supervisor) ÷ 2. HR ratings default to this average; adjust as needed.
          </p>

          <el-table :data="outputs" border size="small" max-height="440" class="ratings-table">
            <el-table-column prop="output" label="Output" min-width="120" show-overflow-tooltip fixed="left" />
            <el-table-column prop="accomplishment" label="Accomplishment" min-width="100" show-overflow-tooltip />
            <el-table-column label="Employee" align="center">
              <el-table-column label="Q" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.q) }}</template>
              </el-table-column>
              <el-table-column label="E" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.e) }}</template>
              </el-table-column>
              <el-table-column label="T" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.t) }}</template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="Supervisor" align="center">
              <el-table-column label="Q" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_q) }}</template>
              </el-table-column>
              <el-table-column label="E" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_e) }}</template>
              </el-table-column>
              <el-table-column label="T" width="48" align="center">
                <template #default="{ row }">{{ formatRating(row.sup_t) }}</template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="HoA Approved Average" align="center" class-name="hoa-approved-col">
              <el-table-column label="Q" width="52" align="center" class-name="hoa-approved-col">
                <template #default="{ row }"><span class="avg-value">{{ formatAverage(row.avg_q) }}</span></template>
              </el-table-column>
              <el-table-column label="E" width="52" align="center" class-name="hoa-approved-col">
                <template #default="{ row }"><span class="avg-value">{{ formatAverage(row.avg_e) }}</span></template>
              </el-table-column>
              <el-table-column label="T" width="52" align="center" class-name="hoa-approved-col">
                <template #default="{ row }"><span class="avg-value">{{ formatAverage(row.avg_t) }}</span></template>
              </el-table-column>
              <el-table-column label="A" width="52" align="center" class-name="hoa-approved-col">
                <template #default="{ row }"><span class="avg-value">{{ formatAverage(row.avg_a) }}</span></template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="HR Recalibration" align="center">
              <el-table-column label="Q" width="58" align="center">
                <template #default="{ row }">
                  <el-input
                    v-model="row.hr_q"
                    size="small"
                    :disabled="!canEdit"
                    @input="onRatingInput(row, 'hr_q')"
                    style="text-align: center"
                  />
                </template>
              </el-table-column>
              <el-table-column label="E" width="58" align="center">
                <template #default="{ row }">
                  <el-input
                    v-model="row.hr_e"
                    size="small"
                    :disabled="!canEdit"
                    @input="onRatingInput(row, 'hr_e')"
                    style="text-align: center"
                  />
                </template>
              </el-table-column>
              <el-table-column label="T" width="58" align="center">
                <template #default="{ row }">
                  <el-input
                    v-model="row.hr_t"
                    size="small"
                    :disabled="!canEdit"
                    @input="onRatingInput(row, 'hr_t')"
                    style="text-align: center"
                  />
                </template>
              </el-table-column>
              <el-table-column label="A" width="58" align="center">
                <template #default="{ row }">
                  <el-input v-model="row.hr_a" size="small" disabled style="text-align: center" />
                </template>
              </el-table-column>
            </el-table-column>
            <el-table-column label="Remarks" min-width="100">
              <template #default="{ row }">
                <el-input v-model="row.hr_remarks" size="small" :disabled="!canEdit" placeholder="Remarks" />
              </template>
            </el-table-column>
          </el-table>
        </div>

        <template #footer>
          <el-button @click="dialogVisible = false">Close</el-button>
          <el-button v-if="canEdit" type="warning" :loading="saving" @click="saveHRRecalibration">
            Save HR Recalibration
          </el-button>
        </template>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { ElMessage } from 'element-plus'
import { View } from '@element-plus/icons-vue'

export default {
  name: 'IPCRHRRecalibration',
  components: { MainLayout },
  data() {
    return {
      View,
      loading: true,
      tableLoading: false,
      saving: false,
      isHR: false,
      activeTab: 'pending',
      pendingRecords: [],
      completedRecords: [],
      dialogVisible: false,
      dialogLoading: false,
      dialogData: null,
      outputs: [],
      activeRecord: null,
      breadcrumbs: [
        { name: 'IPCR', path: '/ipcr' },
        { name: 'HR Recalibration', path: '/ipcr/hr-recalibration' }
      ]
    }
  },
  computed: {
    currentRecords() {
      return this.activeTab === 'completed' ? this.completedRecords : this.pendingRecords
    },
    emptyTableText() {
      return this.activeTab === 'completed'
        ? 'No HR-recalibrated IPCR records yet'
        : 'No IPCR records awaiting HR recalibration'
    },
    canEdit() {
      return this.dialogData?.recalibration_status === 'agency_head_approved'
    }
  },
  async mounted() {
    await this.loadRecords()
  },
  methods: {
    tabLabel(name, count) {
      return count > 0 ? `${name} (${count})` : name
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    formatPeriod(period) {
      if (!period) return '—'
      if (Array.isArray(period) && period.length >= 2) {
        return `${this.formatDate(period[0])} – ${this.formatDate(period[1])}`
      }
      if (typeof period === 'string') return period
      return '—'
    },
    formatStatus(status) {
      const labels = {
        agency_head_approved: 'Approved by Head of Agency',
        hr_recalibrated: 'HR Recalibrated',
        pmt_recalibrated: 'PMT Recalibrated'
      }
      return labels[status] || String(status || '').replace(/_/g, ' ')
    },
    statusTagType(row) {
      const status = row?.recalibration_status
      if (status === 'agency_head_approved') return 'success'
      if (status === 'hr_recalibrated') return 'warning'
      return 'info'
    },
    parseRating(value) {
      const n = Number(value)
      if (!Number.isFinite(n) || n < 2 || n > 5) return null
      return Math.round(n)
    },
    resolveRecalibrationRating(...candidates) {
      for (const v of candidates) {
        const n = this.parseRating(v)
        if (n !== null) return n
      }
      return 2
    },
    formatRating(value) {
      const n = this.parseRating(value)
      return n === null ? '—' : n
    },
    formatAverage(value) {
      if (value === null || value === undefined || value === '') return '—'
      const n = Number(value)
      if (!Number.isFinite(n) || n < 2 || n > 5) return '—'
      return Number.isInteger(n) ? String(n) : n.toFixed(2)
    },
    pairAverage(empVal, supVal) {
      const emp = this.parseRating(empVal)
      const sup = this.parseRating(supVal)
      if (emp === null || sup === null) return null
      return Math.round(((emp + sup) / 2) * 100) / 100
    },
    computeAverage(q, e, t) {
      const values = [q, e, t].filter(v => v !== null && Number.isFinite(v))
      if (!values.length) return ''
      const avg = values.reduce((s, v) => s + v, 0) / values.length
      return (Math.round(Math.max(2, Math.min(5, avg)) * 100) / 100).toFixed(2)
    },
    onRatingInput(row, key) {
      let v = String(row[key] ?? '').replace(/[^0-9]/g, '')
      if (v === '' || v === '0' || v === '1') {
        row[key] = ''
        row.hr_a = this.computeAverage(this.parseRating(row.hr_q), this.parseRating(row.hr_e), this.parseRating(row.hr_t))
        return
      }
      row[key] = Math.max(2, Math.min(5, parseInt(v, 10)))
      row.hr_a = this.computeAverage(this.parseRating(row.hr_q), this.parseRating(row.hr_e), this.parseRating(row.hr_t))
    },
    mapOutputs(apiOutputs) {
      return (apiOutputs || []).map((o) => {
        const sup = o.supervisor_recalibration || {}
        const hr = o.hr_recalibration || {}
        const avgQ = this.pairAverage(o.q, sup.q)
        const avgE = this.pairAverage(o.e, sup.e)
        const avgT = this.pairAverage(o.t, sup.t)
        const avgValues = [avgQ, avgE, avgT].filter(v => v !== null)
        const avgA = avgValues.length
          ? Math.round((avgValues.reduce((s, v) => s + v, 0) / avgValues.length) * 100) / 100
          : null

        const hrQ = this.parseRating(hr.q) ?? (avgQ !== null ? Math.round(avgQ) : null)
        const hrE = this.parseRating(hr.e) ?? (avgE !== null ? Math.round(avgE) : null)
        const hrT = this.parseRating(hr.t) ?? (avgT !== null ? Math.round(avgT) : null)

        return {
          id: o.id,
          output: o.output || '',
          accomplishment: o.accomplishment || '',
          q: o.q,
          e: o.e,
          t: o.t,
          sup_q: sup.q,
          sup_e: sup.e,
          sup_t: sup.t,
          avg_q: avgQ,
          avg_e: avgE,
          avg_t: avgT,
          avg_a: avgA,
          hr_q: hrQ ?? '',
          hr_e: hrE ?? '',
          hr_t: hrT ?? '',
          hr_a: this.computeAverage(hrQ, hrE, hrT) || (avgA !== null ? this.formatAverage(avgA) : ''),
          hr_remarks: hr.remarks || ''
        }
      })
    },
    async loadRecords() {
      try {
        this.loading = true
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.getIPCRHRRecalibrations()
        if (response?.success) {
          this.isHR = !!response.data?.is_hr
          this.pendingRecords = response.data?.pending || []
          this.completedRecords = response.data?.completed || []
        } else {
          ElMessage.error(response?.message || 'Failed to load HR recalibration queue')
        }
      } catch (error) {
        console.error(error)
        ElMessage.error('Failed to load HR recalibration queue')
      } finally {
        this.loading = false
      }
    },
    async openRecalibration(record) {
      this.activeRecord = record
      this.dialogVisible = true
      this.dialogLoading = true
      this.dialogData = null
      this.outputs = []

      try {
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.getEmployeeIPCR(record.id)
        if (response?.success) {
          this.dialogData = response.data
          this.outputs = this.mapOutputs(response.data.outputs)
        } else {
          ElMessage.error(response?.message || 'Failed to load IPCR')
          this.dialogVisible = false
        }
      } catch (error) {
        console.error(error)
        ElMessage.error('Failed to load IPCR')
        this.dialogVisible = false
      } finally {
        this.dialogLoading = false
      }
    },
    async saveHRRecalibration() {
      if (!this.activeRecord?.id) return

      const payloadOutputs = this.outputs.map((row) => {
        const q = this.resolveRecalibrationRating(row.hr_q, row.avg_q, row.q)
        const e = this.resolveRecalibrationRating(row.hr_e, row.avg_e, row.e)
        const t = this.resolveRecalibrationRating(row.hr_t, row.avg_t, row.t)
        const values = [q, e, t]
        const a = Math.round((values.reduce((s, v) => s + v, 0) / values.length) * 100) / 100
        return {
          id: row.id,
          q,
          e,
          t,
          a,
          remarks: row.hr_remarks || ''
        }
      })

      try {
        this.saving = true
        const ApiService = (await import('@/services/api.js')).default
        const response = await ApiService.saveIPCRRecalibration(this.activeRecord.id, 'hr', {
          outputs: payloadOutputs
        })
        if (response?.success) {
          ElMessage.success('HR recalibration saved successfully')
          this.dialogVisible = false
          await this.loadRecords()
        } else {
          ElMessage.error(response?.message || 'Failed to save HR recalibration')
        }
      } catch (error) {
        console.error(error)
        ElMessage.error('Failed to save HR recalibration')
      } finally {
        this.saving = false
      }
    }
  }
}
</script>

<style scoped>
.ratings-table :deep(.el-table__header th) {
  background-color: #f8fafc;
}
.ratings-table :deep(th.hoa-approved-col),
.ratings-table :deep(td.hoa-approved-col) {
  background-color: #ecfdf5;
}
.avg-value {
  font-weight: 600;
  color: #047857;
}
</style>
