<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 mb-2">Training Records</h1>
          <p class="text-slate-600">Track your training and professional development</p>
        </div>
        <el-button type="primary" @click="openAddModal">
          + Add Training
        </el-button>
      </div>

      <el-card shadow="never" v-loading="loading">
        <el-table :data="records" stripe empty-text="No training records yet">
          <el-table-column prop="training_name" label="Training Name" min-width="200" />
          <el-table-column label="Date Attended" width="130">
            <template #default="{ row }">{{ formatDate(row.date_attended) }}</template>
          </el-table-column>
          <el-table-column prop="duration" label="Duration (hours)" width="130" align="center" />
          <el-table-column prop="provider" label="Provider" min-width="180" />
          <el-table-column label="Certificate" width="100" align="center">
            <template #default="{ row }">
              <el-icon v-if="row.has_certificate"><Check /></el-icon>
              <span v-else class="text-slate-400">—</span>
            </template>
          </el-table-column>
          <el-table-column label="Actions" width="100" fixed="right">
            <template #default="{ row }">
              <el-button type="primary" link size="small" @click="viewRecord(row)">View</el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <!-- Add/Edit Modal -->
      <el-dialog v-model="formVisible" title="Add Training Record" width="600px">
        <el-form ref="formRef" :model="form" :rules="rules" label-width="140px">
          <el-form-item label="Training Name" prop="training_name">
            <el-input v-model="form.training_name" placeholder="e.g. Leadership Development" />
          </el-form-item>
          <el-form-item label="Date Attended" prop="date_attended">
            <el-date-picker v-model="form.date_attended" type="date" style="width: 100%" />
          </el-form-item>
          <el-form-item label="Duration (hours)" prop="duration">
            <el-input-number v-model="form.duration" :min="1" style="width: 100%" />
          </el-form-item>
          <el-form-item label="Provider" prop="provider">
            <el-input v-model="form.provider" placeholder="e.g. Training Institute Name" />
          </el-form-item>
          <el-form-item label="Has Certificate">
            <el-checkbox v-model="form.has_certificate">Yes, I have a certificate</el-checkbox>
          </el-form-item>
          <el-form-item label="Notes" prop="notes">
            <el-input v-model="form.notes" type="textarea" :rows="4" placeholder="Additional details (optional)" />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="formVisible = false">Cancel</el-button>
          <el-button type="primary" @click="submitForm" :loading="submitting">Save</el-button>
        </template>
      </el-dialog>

      <!-- View Modal -->
      <el-dialog v-model="detailVisible" title="Training Details" width="500px">
        <div v-if="selectedRecord" class="space-y-3">
          <div><span class="font-semibold text-slate-600">Training Name:</span> {{ selectedRecord.training_name }}</div>
          <div><span class="font-semibold text-slate-600">Date:</span> {{ formatDate(selectedRecord.date_attended) }}</div>
          <div><span class="font-semibold text-slate-600">Duration:</span> {{ selectedRecord.duration }} hours</div>
          <div><span class="font-semibold text-slate-600">Provider:</span> {{ selectedRecord.provider }}</div>
          <div><span class="font-semibold text-slate-600">Certificate:</span> {{ selectedRecord.has_certificate ? 'Yes' : 'No' }}</div>
          <div v-if="selectedRecord.notes"><span class="font-semibold text-slate-600">Notes:</span> {{ selectedRecord.notes }}</div>
        </div>
      </el-dialog>
    </div>
  </MainLayout>
</template>

<script>
import MainLayout from '@/layout/MainLayout.vue'
import { trainingRecordApiService } from '@/services/apiService.js'
import { ElMessage } from 'element-plus'
import { Check } from '@element-plus/icons-vue'

export default {
  name: 'TrainingRecord',
  components: { MainLayout, Check },
  data() {
    return {
      loading: false,
      submitting: false,
      formVisible: false,
      detailVisible: false,
      records: [],
      selectedRecord: null,
      form: {
        training_name: '',
        date_attended: null,
        duration: 1,
        provider: '',
        has_certificate: false,
        notes: ''
      },
      rules: {
        training_name: [{ required: true, message: 'Training name is required', trigger: 'blur' }],
        date_attended: [{ required: true, message: 'Date is required', trigger: 'change' }],
        duration: [{ required: true, message: 'Duration is required', trigger: 'blur' }],
        provider: [{ required: true, message: 'Provider is required', trigger: 'blur' }]
      },
      breadcrumbs: [
        { name: 'Dashboard', path: '/dashboard' },
        { name: 'Training Records', path: '/training-records' }
      ]
    }
  },
  async mounted() {
    await this.loadRecords()
  },
  methods: {
    async loadRecords() {
      this.loading = true
      try {
        const response = await trainingRecordApiService.getRecords()
        this.records = response.data?.records || []
      } catch (error) {
        ElMessage.error('Failed to load training records')
      } finally {
        this.loading = false
      }
    },
    openAddModal() {
      this.form = { training_name: '', date_attended: null, duration: 1, provider: '', has_certificate: false, notes: '' }
      this.formVisible = true
    },
    async submitForm() {
      try {
        await this.$refs.formRef.validate()
      } catch {
        return
      }
      this.submitting = true
      try {
        await trainingRecordApiService.createRecord(this.form)
        ElMessage.success('Training record added')
        this.formVisible = false
        await this.loadRecords()
      } catch (error) {
        ElMessage.error('Failed to save training record')
      } finally {
        this.submitting = false
      }
    },
    viewRecord(row) {
      this.selectedRecord = row
      this.detailVisible = true
    },
    formatDate(value) {
      if (!value) return '—'
      return new Date(value).toLocaleDateString()
    }
  }
}
</script>