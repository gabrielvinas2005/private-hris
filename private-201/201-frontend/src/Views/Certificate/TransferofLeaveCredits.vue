<template>
  <div class="page-container">
    <el-card shadow="hover" class="transfer-card">
      <template #header>
        <div class="card-header">
          <div>
            <h2 class="card-title">Transfer of Leave Credits</h2>
            <p class="card-subtitle">
              View and record leave credits for employees.
            </p>
          </div>
        </div>
      </template>

      <el-form
        :model="formData"
        :rules="rules"
        ref="formRef"
        label-width="auto"
        class="card-body"
      >
        <el-form-item label="Employee" prop="employee_id">
          <el-select
            v-model="formData.employee_id"
            placeholder="Select employee"
            filterable
            clearable
            class="w-full"
            :loading="loadingEmployees"
            @change="handleEmployeeChange"
          >
            <el-option
              v-for="emp in employees"
              :key="emp.id"
              :label="formatEmployeeLabel(emp)"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>

        <el-divider content-position="left">Available Leave Credits</el-divider>

        <el-form-item label="Vacation Leave">
          <el-input
            v-model="formData.vacation_leave_credits"
            :disabled="true"
            placeholder="Select an employee to view credits"
            :loading="loadingCredits"
          >
            <template #prefix>
              <span style="color: #909399;">{{ leaveCredits.vacation_leave.leave_type_name }}:</span>
            </template>
          </el-input>
        </el-form-item>

        <el-form-item label="Sick Leave">
          <el-input
            v-model="formData.sick_leave_credits"
            :disabled="true"
            placeholder="Select an employee to view credits"
            :loading="loadingCredits"
          >
            <template #prefix>
              <span style="color: #909399;">{{ leaveCredits.sick_leave.leave_type_name }}:</span>
            </template>
          </el-input>
        </el-form-item>

        <el-form-item>
          <el-button @click="onReset">Reset</el-button>
          <el-button type="primary" :loading="submitting" @click="onSubmit">
            Save
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <el-card shadow="never" class="mt-4">
      <div class="table-header">
        <h3 class="table-title">Transfer Leave Credits Records</h3>
      </div>
      <el-table
        :data="transferList"
        stripe
        style="width: 100%"
        height="360"
        v-loading="loadingTransfers"
        element-loading-text="Loading transfer records..."
      >
        <el-table-column prop="employee_name" label="Employee" min-width="180" />
        <el-table-column prop="vacation_leave_credits" label="Vacation Leave" width="140" align="right">
          <template #default="{ row }">
            {{ row.vacation_leave_credits || 0 }}
          </template>
        </el-table-column>
        <el-table-column prop="sick_leave_credits" label="Sick Leave" width="140" align="right">
          <template #default="{ row }">
            {{ row.sick_leave_credits || 0 }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="Created At" width="180" />
        <el-table-column label="Actions" width="80" align="center">
          <template #default="{ row }">
            <el-button
              type="primary"
              text
              size="small"
              @click="handlePrint(row)"
            >
              <el-icon>
                <Printer />
              </el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-card v-if="pdfUrl" class="preview-card" shadow="never">
      <div class="table-header preview-header">
        <h3 class="table-title">Report Preview — {{ selectedRecord?.employee_name || '' }}</h3>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">Download as:</span>
          <el-button type="danger" size="small" @click="downloadPdf">PDF</el-button>
          <el-button type="primary" size="small" @click="downloadWord">WORD</el-button>
        </div>
      </div>

      <div class="preview-edit-panel" v-loading="previewLoading">
        <el-form label-position="top" class="preview-form">
          <el-form-item label="Purpose Text">
            <el-input
              v-model="previewFormData.purpose_text"
              type="textarea"
              :rows="3"
              placeholder="Certification purpose paragraph"
              @blur="updatePreview"
            />
          </el-form-item>

          <el-divider content-position="left">Certification Reasons (Items 1–4)</el-divider>

          <el-form-item label="Reason 1">
            <el-input v-model="previewFormData.reason_1" type="textarea" :rows="2" @blur="updatePreview" />
          </el-form-item>
          <el-form-item label="Reason 2">
            <el-input v-model="previewFormData.reason_2" type="textarea" :rows="2" @blur="updatePreview" />
          </el-form-item>
          <el-form-item label="Reason 3">
            <el-input v-model="previewFormData.reason_3" type="textarea" :rows="2" @blur="updatePreview" />
          </el-form-item>
          <el-form-item label="Reason 4">
            <el-input v-model="previewFormData.reason_4" type="textarea" :rows="2" @blur="updatePreview" />
          </el-form-item>

          <el-divider content-position="left">Type of Leave (Table Rows)</el-divider>

          <div
            v-for="(row, index) in previewFormData.leave_rows"
            :key="index"
            class="leave-row-editor"
          >
            <div class="leave-row-title">Leave Row {{ index + 1 }}</div>
            <el-form-item label="Type of Leave">
              <el-input v-model="row.type" @blur="updatePreview" />
            </el-form-item>
            <el-form-item label="No. of days availed">
              <el-input v-model="row.days" @blur="updatePreview" />
            </el-form-item>
            <el-form-item label="Date/s leave was availed">
              <el-input v-model="row.dates" @blur="updatePreview" />
            </el-form-item>
          </div>
        </el-form>
        <p class="preview-hint">Edit fields above; the preview refreshes when you leave each field.</p>
      </div>

      <iframe class="pdf-frame" :src="pdfUrl" :key="previewKey"></iframe>
    </el-card>
  </div>
</template>

<script setup>
import { Printer } from '@element-plus/icons-vue'
import { useTransferLeaveCredits } from '../../composable/useTransferLeaveCredits'

const {
  formRef,
  employees,
  transferList,
  pdfUrl,
  previewKey,
  previewFormData,
  previewLoading,
  selectedRecord,
  loadingEmployees,
  loadingCredits,
  loadingTransfers,
  submitting,
  formData,
  leaveCredits,
  rules,
  formatEmployeeLabel,
  onSubmit,
  onReset,
  handleEmployeeChange,
  handlePrint,
  updatePreview,
  downloadPdf,
  downloadWord
} = useTransferLeaveCredits()
</script>

<style scoped>
.page-container {
  padding: 1.5rem 2rem;
}

.transfer-card {
  margin-bottom: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(90deg, #f3f4ff, #eef2ff);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.card-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mt-4 {
  margin-top: 1rem;
}

.table-header {
  margin-bottom: 0.5rem;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.table-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.w-full {
  width: 100%;
}

.preview-card {
  margin-top: 1rem;
}

.preview-edit-panel {
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.preview-form {
  max-width: 900px;
}

.leave-row-editor {
  margin-bottom: 0.75rem;
  padding: 0.75rem;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.leave-row-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.preview-hint {
  margin: 0.5rem 0 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.pdf-frame {
  width: 100%;
  height: 520px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}
</style>
