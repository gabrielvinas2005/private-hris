<template>
  <div class="page-container">
    <el-card shadow="hover" class="request-card">
      <template #header>
        <div class="card-header">
          <div>
            <h2 class="card-title">Request for Publication</h2>
            <p class="card-subtitle">
              View plantillas that are due for publication and generate reports.
            </p>
          </div>
          <div>
            <el-button type="primary" @click="openPlantillas" :loading="loadingPlantillas">
              Request for Publication
            </el-button>
          </div>
        </div>
      </template>

      <div
        class="card-body"
        v-if="showPlantillas"
        v-loading="generateLoading"
        element-loading-text="Generating publication report..."
        element-loading-background="rgba(255, 255, 255, 0.85)"
      >
        <!-- Employee Selection Form -->
        <el-card shadow="never" style="margin-bottom: 1.5rem;">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">HRMO Selection</h3>
            <p class="text-sm text-gray-500 mt-1">Select HRMO employee for the report</p>
          </div>
          <div class="p-6">
            <el-form :model="reportFormData" label-width="140px" v-loading="loadingFormData">
              <el-form-item label="HRMO" prop="hrmo_employee_id">
                <el-select
                  v-model="reportFormData.hrmo_employee_id"
                  placeholder="Select employee"
                  filterable
                  clearable
                  style="width: 100%"
                >
                  <el-option
                    v-for="emp in hrmoEmployees"
                    :key="emp.id"
                    :label="emp.position_title ? `${emp.name} - ${emp.position_title}` : emp.name"
                    :value="emp.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="Agency Email">
                <div class="email-save-row">
                  <el-input
                    v-model="reportFormData.hrmo_email"
                    type="email"
                    placeholder="mail@agency.gov.ph"
                    clearable
                  />
                  <el-button
                    type="primary"
                    :loading="savingHrmoEmail"
                    @click="saveHrmoEmail"
                  >
                    Save
                  </el-button>
                </div>
                <p class="field-hint">Shown on the form as the agency email for qualified applicants.</p>
              </el-form-item>
            </el-form>
          </div>
        </el-card>

        <el-table
          :data="plantillas"
          stripe
          style="width: 100%"
          height="400"
          v-loading="loadingPlantillas"
          element-loading-text="Loading plantillas..."
        >
          <!-- Position & item no -->
          <el-table-column prop="position_name" label="Position" min-width="220" />
          <el-table-column prop="code" label="Item No." width="140" />

          <!-- Salary info -->
          <el-table-column label="Salary Grade / Step" width="180">
            <template #default="{ row }">
              {{ row.salary_grade_name }} / {{ row.salary_step_name }}
            </template>
          </el-table-column>
          <el-table-column label="Monthly Salary" width="150">
            <template #default="{ row }">
              {{ row.monthly_salary || '-' }}
            </template>
          </el-table-column>

          <!-- Qualification standards -->
          <el-table-column prop="education" label="Education" min-width="200" />
          <el-table-column prop="training" label="Training" min-width="200" />
          <el-table-column prop="experience" label="Experience" min-width="200" />
          <el-table-column prop="eligibility" label="Eligibility" min-width="200" />
          <el-table-column prop="competency" label="Competency" min-width="200">
            <template #default="{ row }">
              {{ row.competency || '-' }}
            </template>
          </el-table-column>
        </el-table>

        <div class="actions">
          <el-button
            type="primary"
            :disabled="!plantillas.length || generateLoading"
            :loading="generateLoading"
            @click="handleGenerateReport"
          >
            {{ generateLoading ? 'Generating...' : 'Generate Report' }}
          </el-button>
        </div>
      </div>
    </el-card>

    <el-card v-if="generateLoading" class="preview-card generate-loading-card" shadow="never">
      <div class="generate-loading-panel" role="status" aria-live="polite" aria-busy="true">
        <div class="generate-spinner" aria-hidden="true"></div>
        <p class="generate-loading-title">Generating your report</p>
        <p class="generate-loading-subtitle">Please wait while the PDF is being prepared...</p>
        <div class="generate-progress-track" aria-hidden="true">
          <div class="generate-progress-bar"></div>
        </div>
      </div>
    </el-card>

    <el-card v-else-if="pdfUrl" class="preview-card" shadow="never">
      <div class="table-header" style="display:flex; justify-content: space-between; align-items: center;">
        <h3 class="table-title">Report Preview</h3>
        <div>
          <el-button size="small" @click="downloadPdf">Download</el-button>
        </div>
      </div>
      <iframe class="pdf-frame" :src="pdfUrl"></iframe>
    </el-card>
  </div>
</template>

<script setup>
import { useRequestforpublication } from '../../composable/useRequestforpublication'

const {
  // state
  plantillas,
  showPlantillas,
  loadingPlantillas,
  pdfUrl,
  loadingFormData,
  hrmoEmployees,
  reportFormData,
  savingHrmoEmail,
  generateLoading,
  // actions
  openPlantillas,
  handleGenerateReport,
  saveHrmoEmail,
  downloadPdf
} = useRequestforpublication()
</script>

<style scoped>
.page-container {
  padding: 1.5rem 2rem;
}

.request-card {
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

.table-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}

.preview-card {
  margin-top: 1rem;
}

.pdf-frame {
  width: 100%;
  height: 520px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.w-full {
  width: 100%;
}

.email-save-row {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}

.email-save-row .el-input {
  flex: 1;
}

.field-hint {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.generate-loading-card {
  margin-top: 1rem;
}

.generate-loading-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 280px;
  padding: 2rem 1.5rem;
  text-align: center;
}

.generate-spinner {
  width: 52px;
  height: 52px;
  border: 4px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: generate-spin 0.9s linear infinite;
}

.generate-loading-title {
  margin: 1.25rem 0 0.35rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: #111827;
}

.generate-loading-subtitle {
  margin: 0 0 1.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.generate-progress-track {
  width: min(320px, 100%);
  height: 6px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
}

.generate-progress-bar {
  width: 40%;
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #4f46e5);
  border-radius: 999px;
  animation: generate-progress 1.4s ease-in-out infinite;
}

@keyframes generate-spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes generate-progress {
  0% {
    transform: translateX(-120%);
  }
  100% {
    transform: translateX(320%);
  }
}

</style>