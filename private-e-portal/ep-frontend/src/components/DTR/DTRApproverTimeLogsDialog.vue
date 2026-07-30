<template>
  <el-dialog
    v-model="visible"
    :title="dialogTitle"
    width="95%"
    top="3vh"
    destroy-on-close
    @closed="resetState"
  >
    <div v-loading="loading">
      <div v-if="header" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div><span class="text-slate-500">Employee:</span> {{ header.name }}</div>
        <div><span class="text-slate-500">Department:</span> {{ header.department }}</div>
        <div><span class="text-slate-500">Payroll Period:</span> {{ payrollPeriodLabel }}</div>
      </div>

      <div class="mb-4 flex flex-wrap items-end gap-4">
        <div v-if="!hasFixedDateRange">
          <label class="block text-sm font-medium text-slate-700 mb-1">From</label>
          <el-date-picker
            v-model="dateRange.from"
            type="date"
            value-format="YYYY-MM-DD"
            format="MM/DD/YYYY"
            style="width: 180px"
          />
        </div>
        <div v-if="!hasFixedDateRange">
          <label class="block text-sm font-medium text-slate-700 mb-1">To</label>
          <el-date-picker
            v-model="dateRange.to"
            type="date"
            value-format="YYYY-MM-DD"
            format="MM/DD/YYYY"
            style="width: 180px"
          />
        </div>
        <el-button v-if="!hasFixedDateRange" type="primary" :loading="loadingLogs" @click="loadLogsFromRange">
          Load Logs
        </el-button>
        <div class="min-w-[240px]">
          <label class="block text-sm font-medium text-slate-700 mb-1">Assign to Payroll Period</label>
          <el-select v-model="payrollPeriodId" placeholder="Select payroll period" style="width: 100%">
            <el-option
              v-for="period in payrollPeriods"
              :key="period.id"
              :label="period.name"
              :value="period.id"
            />
          </el-select>
        </div>
      </div>

      <el-alert
        class="mb-4"
        type="info"
        :closable="false"
        show-icon
        title="Select the dates to correct, edit the time in/out values, then save."
      />

      <el-table
        ref="logsTableRef"
        :data="timeLogs"
        border
        stripe
        size="small"
        max-height="420"
        empty-text="No time logs loaded for this request"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="48" />
        <el-table-column label="Date" width="130">
          <template #default="{ row }">
            {{ formatDate(row.date) }}
          </template>
        </el-table-column>
        <el-table-column label="AM In" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.am_in"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="AM Out" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.am_out"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="Break In" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.break_in"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="Break Out" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.break_out"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="PM In" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.pm_in"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
        <el-table-column label="PM Out" width="130">
          <template #default="{ row }">
            <el-time-picker
              v-model="row.pm_out"
              format="HH:mm"
              value-format="HH:mm"
              placeholder="--:--"
              size="small"
              style="width: 100%"
            />
          </template>
        </el-table-column>
      </el-table>
    </div>

    <template #footer>
      <el-button @click="visible = false">Cancel</el-button>
      <el-button type="primary" :loading="saving" :disabled="!canSave" @click="saveCorrections">
        Save Time Log Corrections
      </el-button>
    </template>
  </el-dialog>
</template>

<script>
import { dtrApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'

export default {
  name: 'DTRApproverTimeLogsDialog',
  emits: ['saved'],
  data() {
    return {
      visible: false,
      loading: false,
      loadingLogs: false,
      saving: false,
      requestId: null,
      header: null,
      payrollPeriods: [],
      payrollPeriodId: null,
      payrollPeriodLabel: '—',
      dateRange: { from: '', to: '' },
      hasFixedDateRange: false,
      timeLogs: [],
      selectedRows: []
    }
  },
  computed: {
    dialogTitle() {
      return this.header?.name ? `Time Logs — ${this.header.name}` : 'Time Logs'
    },
    canSave() {
      return this.requestId && this.payrollPeriodId && this.selectedRows.length > 0
    }
  },
  methods: {
    unwrapApiPayload(response) {
      if (!response || typeof response !== 'object') return response
      if (response.success === false) {
        throw new Error(response.message || 'Request failed')
      }
      return response.data ?? response
    },
    async open(requestRow) {
      this.requestId = requestRow.id
      this.visible = true
      this.loading = true

      try {
        const response = await dtrApiService.reviewDTRRequest(requestRow.id)
        const review = this.unwrapApiPayload(response)

        this.header = review?.daily_time_records?.[0] || requestRow
        const periods = review?.payroll_periods
        this.payrollPeriods = Array.isArray(periods) ? periods : []

        const attachment = review.application_attachment
        const defaultPeriodId = attachment?.payroll_period_id
          || this.header?.payroll_period_id
          || null

        this.payrollPeriodId = defaultPeriodId
        this.payrollPeriodLabel = attachment?.payroll_period || '—'

        const start = attachment?.attendance_start_date
        const end = attachment?.attendance_end_date

        if (start && end) {
          this.hasFixedDateRange = true
          this.dateRange.from = this.formatDateOnly(start)
          this.dateRange.to = this.formatDateOnly(end)
        } else {
          this.hasFixedDateRange = false
        }

        const logs = (review.time_data || []).map(log => this.normalizeLog(log))
        if (logs.length > 0) {
          this.timeLogs = logs
          this.selectAllRows()
        } else if (this.hasFixedDateRange) {
          await this.loadLogsFromRange()
        } else {
          this.timeLogs = []
        }
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || error?.message || 'Failed to load time logs')
        this.visible = false
      } finally {
        this.loading = false
      }
    },
    async loadLogsFromRange() {
      if (!this.header?.employee_id || !this.dateRange.from || !this.dateRange.to) {
        ElMessage.warning('Select a date range to load time logs')
        return
      }

      try {
        this.loadingLogs = true
        const response = await dtrApiService.getTimeLogs(
          this.header.employee_id,
          this.dateRange.from,
          this.dateRange.to,
          { skipBioSync: true }
        )

        let logs = response
        if (typeof logs === 'string') {
          logs = JSON.parse(logs)
        }
        if (logs?.data) {
          logs = logs.data
        }

        this.timeLogs = (logs || []).map(log => this.normalizeLog(log))
        this.selectAllRows()
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to load time logs')
        this.timeLogs = []
      } finally {
        this.loadingLogs = false
      }
    },
    normalizeLog(log) {
      return {
        ...log,
        id: log?.id ?? null,
        date: log?.date ?? '',
        am_in: this.toTimeValue(log.am_in),
        am_out: this.toTimeValue(log.am_out),
        break_in: this.toTimeValue(log.break_in),
        break_out: this.toTimeValue(log.break_out),
        pm_in: this.toTimeValue(log.pm_in),
        pm_out: this.toTimeValue(log.pm_out)
      }
    },
    toTimeValue(value) {
      if (!value) return ''
      const match = String(value).match(/(\d{1,2}):(\d{2})/)
      if (!match) return ''
      return `${match[1].padStart(2, '0')}:${match[2]}`
    },
    formatDateOnly(value) {
      if (!value) return ''
      const d = new Date(value)
      if (Number.isNaN(d.getTime())) {
        return String(value).slice(0, 10)
      }
      return d.toISOString().slice(0, 10)
    },
    formatDate(value) {
      if (!value) return '—'
      const d = new Date(value)
      return Number.isNaN(d.getTime()) ? value : d.toLocaleDateString()
    },
    handleSelectionChange(rows) {
      this.selectedRows = rows
    },
    selectAllRows() {
      this.$nextTick(() => {
        const table = this.$refs.logsTableRef
        if (!table) return
        table.clearSelection()
        this.timeLogs.forEach(row => table.toggleRowSelection(row, true))
        this.selectedRows = [...this.timeLogs]
      })
    },
    async saveCorrections() {
      if (!this.canSave) {
        ElMessage.warning('Select at least one date and a payroll period')
        return
      }

      const formData = new FormData()
      formData.append('payroll_period_id', this.payrollPeriodId)

      this.timeLogs.forEach((log, index) => {
        formData.append(`date[${index}]`, this.formatDateOnly(log.date))
        formData.append(`id[${index}]`, log.id != null ? String(log.id) : '')
        formData.append(`am_in[${index}]`, log.am_in || '')
        formData.append(`am_out[${index}]`, log.am_out || '')
        formData.append(`break_in[${index}]`, log.break_in || '')
        formData.append(`break_out[${index}]`, log.break_out || '')
        formData.append(`pm_in[${index}]`, log.pm_in || '')
        formData.append(`pm_out[${index}]`, log.pm_out || '')
      })

      this.selectedRows.forEach((log) => {
        formData.append('select[]', this.formatDateOnly(log.date))
      })

      try {
        this.saving = true
        await dtrApiService.approveDTRRequest(this.requestId, 1, formData)
        ElMessage.success('Time log corrections saved')
        this.visible = false
        this.$emit('saved')
      } catch (error) {
        ElMessage.error(error?.response?.data?.message || 'Failed to save time log corrections')
      } finally {
        this.saving = false
      }
    },
    resetState() {
      this.requestId = null
      this.header = null
      this.payrollPeriods = []
      this.payrollPeriodId = null
      this.payrollPeriodLabel = '—'
      this.dateRange = { from: '', to: '' }
      this.hasFixedDateRange = false
      this.timeLogs = []
      this.selectedRows = []
    }
  }
}
</script>
