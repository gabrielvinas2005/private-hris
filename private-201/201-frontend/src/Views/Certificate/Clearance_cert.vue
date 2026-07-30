<template>
  <div class="page-container">
    <el-card shadow="hover" class="clearance-card">
      <template #header>
        <div class="card-header">
          <div>
            <h2 class="card-title">Clearance Certificate</h2>
            <p class="card-subtitle">
              Set the filing and effectivity dates for the selected employee’s clearance.
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
        <!-- Employee selection -->
        <el-form-item label="Employee" prop="employee_id">
          <el-select
            v-model="formData.employee_id"
            placeholder="Select employee"
            filterable
            clearable
            class="w-full"
          >
            <el-option
              v-for="emp in employees"
              :key="emp.id"
              :label="formatEmployeeLabel(emp)"
              :value="emp.id"
            />
          </el-select>
        </el-form-item>

        <!-- Purpose selection + Other Mode toggle -->
        <el-form-item label="Purpose">
          <el-row :gutter="12" style="width: 100%">
            <el-col :span="16">
              <el-form-item prop="purpose_id" label-width="0">
                <el-select
                  v-model="formData.purpose_id"
                  placeholder="Select purpose"
                  clearable
                  class="w-full"
                  :disabled="formData.is_other_mode"
                >
                  <el-option
                    v-for="item in purposes"
                    :key="item.id"
                    :label="item.name"
                    :value="item.id"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :span="8" class="other-mode-toggle">
              <el-checkbox v-model="formData.is_other_mode" @change="handleOtherModeToggle">
                Other Mode of Separation
              </el-checkbox>
            </el-col>
          </el-row>
        </el-form-item>

        <!-- Other purpose text -->
        <el-form-item
          v-if="showOtherPurpose"
          label="Please specify"
          prop="Other_purpose"
        >
          <el-input
            v-model="formData.Other_purpose"
            placeholder="Specify other mode of separation"
          />
        </el-form-item>

        <!-- Clearance dates -->
        <el-form-item label="Clearance dates">
          <el-row :gutter="12" style="width: 100%">
            <el-col :span="12">
              <el-form-item label="Date of filing" prop="date_of_filing" label-width="110px">
                <el-date-picker
                  v-model="formData.date_of_filing"
                  type="date"
                  placeholder="Select date of filing"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  class="w-full"
                />
              </el-form-item>
            </el-col>
            <el-col :span="12">
              <el-form-item label="Date of effectivity" prop="date_of_effectivity" label-width="130px">
                <el-date-picker
                  v-model="formData.date_of_effectivity"
                  type="date"
                  placeholder="Select date of effectivity"
                  format="YYYY-MM-DD"
                  value-format="YYYY-MM-DD"
                  class="w-full"
                />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form-item>

        <!-- Status switches -->
        <el-form-item label="Status">
          <div class="status-column">
            <div class="status-toggle-row">
              <span class="status-label-left">Not cleared</span>
              <el-switch v-model="formData.is_cleared" />
              <span class="status-label-right">Cleared</span>
            </div>
            <div class="status-toggle-row">
              <span class="status-label-left">No pending admin case</span>
              <el-switch v-model="formData.with_pending_administrative" />
              <span class="status-label-right">With pending admin case</span>
            </div>
            <div class="status-toggle-row">
              <span class="status-label-left">No ongoing investigation</span>
              <el-switch v-model="formData.with_ongoing_investigation" />
              <span class="status-label-right">With ongoing investigation</span>
            </div>
          </div>
        </el-form-item>

        <!-- Clearing Officers Section -->
        <el-divider content-position="left">
          <span style="font-weight: 600; color: #409eff; font-size: 16px;">Clearing Officers</span>
        </el-divider>
        <p style="color: #909399; font-size: 13px; margin: -10px 0 20px 0;">
          Select clearing officers for each department/service. Click "Save Clearing Officers" to save your selections.
        </p>

        <div class="clearing-officers-container">
          <!-- Administrative Services Card -->
          <el-card shadow="hover" class="clearing-officer-card">
            <template #header>
              <div class="card-header-content">
                <span class="card-number">1</span>
                <span class="card-title">Administrative Services</span>
              </div>
            </template>
            <div class="clearing-officer-items">
              <div class="clearing-officer-item">
                <span class="item-label">a. Supply and Property Procurement & Management Services</span>
                <el-select
                  v-model="formData.clearing_officer_supply_property"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
              <div class="clearing-officer-item">
                <span class="item-label">b. Human Resources Welfare & Assistance</span>
                <el-select
                  v-model="formData.clearing_officer_hr_welfare"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
              <div class="clearing-officer-item">
                <span class="item-label">c. Agency-Accredited Union/Cooperative</span>
                <el-select
                  v-model="formData.clearing_officer_union_cooperative"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
            </div>
          </el-card>

          <!-- Library Card -->
          <el-card shadow="hover" class="clearing-officer-card">
            <template #header>
              <div class="card-header-content">
                <span class="card-number">2</span>
                <span class="card-title">Library</span>
              </div>
            </template>
            <div class="clearing-officer-items">
              <div class="clearing-officer-item">
                <span class="item-label">a. Legal Office Library</span>
                <el-select
                  v-model="formData.clearing_officer_legal_library"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
              <div class="clearing-officer-item">
                <span class="item-label">b. Library Services</span>
                <el-select
                  v-model="formData.clearing_officer_library_services"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
            </div>
          </el-card>

          <!-- Finance and Assets Management Card -->
          <el-card shadow="hover" class="clearing-officer-card">
            <template #header>
              <div class="card-header-content">
                <span class="card-number">3</span>
                <span class="card-title">Finance and Assets Management</span>
              </div>
            </template>
            <div class="clearing-officer-items">
              <div class="clearing-officer-item">
                <span class="item-label">a. Financial Services</span>
                <el-select
                  v-model="formData.clearing_officer_financial_services"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
              <div class="clearing-officer-item">
                <span class="item-label">b. Transaction, Processing, Billing Services</span>
                <el-select
                  v-model="formData.clearing_officer_transaction_billing"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
              <div class="clearing-officer-item">
                <span class="item-label">c. Payroll & Remittance Services</span>
                <el-select
                  v-model="formData.clearing_officer_payroll_remittance"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
            </div>
          </el-card>

          <!-- Professional and Institutional Development Card -->
          <el-card shadow="hover" class="clearing-officer-card">
            <template #header>
              <div class="card-header-content">
                <span class="card-number">4</span>
                <span class="card-title">Professional and Institutional Development</span>
              </div>
            </template>
            <div class="clearing-officer-items">
              <div class="clearing-officer-item">
                <span class="item-label">a. Scholarship Services</span>
                <el-select
                  v-model="formData.clearing_officer_scholarship"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
            </div>
          </el-card>

          <!-- Internal Affairs Office Card -->
          <el-card shadow="hover" class="clearing-officer-card">
            <template #header>
              <div class="card-header-content">
                <span class="card-number">5</span>
                <span class="card-title">Certification of No Pending Administrative Case</span>
              </div>
            </template>
            <div class="clearing-officer-items">
              <div class="clearing-officer-item">
                <span class="item-label">Internal Affairs Office/Legal Affairs Office</span>
                <el-select
                  v-model="formData.clearing_officer_internal_affairs"
                  placeholder="Select clearing officer"
                  filterable
                  clearable
                  class="clearing-officer-select"
                >
                  <el-option
                    v-for="officer in clearingOfficers"
                    :key="officer.id"
                    :label="formatClearingOfficerLabel(officer)"
                    :value="officer.id"
                  />
                </el-select>
              </div>
            </div>
          </el-card>
        </div>

        <!-- Save Clearing Officers Button -->
        <div class="clearing-officers-actions">
          <el-button 
            type="primary" 
            @click="handleSaveClearingOfficers"
            :loading="savingClearingOfficers"
          >
            <el-icon v-if="!savingClearingOfficers">
              <Document v-if="!clearingOfficersSaved" />
              <Check v-else />
            </el-icon>
            {{ clearingOfficersSaved ? 'Clearing Officers Saved' : 'Save Clearing Officers' }}
          </el-button>
          <span v-if="clearingOfficersSaved" class="save-success-message">
            <el-icon><Check /></el-icon>
            Selections cached successfully
          </span>
        </div>

        <!-- Actions -->
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
        <h3 class="table-title">Created Clearance Certificates</h3>
      </div>
      <el-table
        :data="clearanceList"
        stripe
        style="width: 100%"
        height="360"
        v-loading="loadingTable"
        element-loading-text="Loading clearance certificates..."
      >
        <el-table-column prop="employee_name" label="Employee" min-width="180" />
        <el-table-column prop="Date_of_filing" label="Date of filing" width="130" />
        <el-table-column prop="Date_of_effectivity" label="Date of effectivity" width="150" />
        <el-table-column label="Purpose" min-width="150">
          <template #default="{ row }">
            <span v-if="row.purpose_name && row.purpose_name !== ''">
              {{ row.purpose_name }}
            </span>
            <span
              v-else-if="row.Other_purpose && row.Other_purpose !== ''"
            >
              N/A
            </span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column
          v-if="hasOtherPurpose"
          label="Other purpose"
          min-width="180"
        >
          <template #default="{ row }">
            <span v-if="row.Other_purpose && row.Other_purpose !== ''">
              {{ row.Other_purpose }}
            </span>
            <span v-else>-</span>
          </template>
        </el-table-column>
        <el-table-column label="Status" min-width="260">
          <template #default="{ row }">
            {{ formatStatus(row) }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="Created at" width="170" />
        <el-table-column label="Actions" width="140" align="center">
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
            <el-button
              type="danger"
              text
              size="small"
              @click="handleDelete(row)"
            >
              <el-icon>
                <Delete />
              </el-icon>
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- Clearance Certificate Preview Modal -->
    <CertificatePreviewModal
      v-if="showPreviewModal"
      :visible="showPreviewModal"
      :pdf-url="previewPdfUrl"
      :employee-name="selectedEmployeeName"
      :certificate-type="'Clearance Certificate'"
      :loading="generateLoading"
      :preview-key="previewKey"
      :show-purpose-edit="false"
      :show-word-button="true"
      :show-excel-button="false"
      @close="handleClosePreview"
      @download="handleDownloadPdf"
      @downloadWord="handleDownloadWord"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Printer, Check, Document, Delete } from '@element-plus/icons-vue'
import { clearanceCertificateApi } from '../../services/api'
import CertificatePreviewModal from '../../components/Certificate/CertificatePreviewModal.vue'

const formRef = ref()
const employees = ref([])
const purposes = ref([])
const clearingOfficers = ref([])
const submitting = ref(false)
const clearanceList = ref([])
const loadingTable = ref(false)
const showPreviewModal = ref(false)
const previewPdfUrl = ref('')
const previewKey = ref(0)
const generateLoading = ref(false)
const selectedEmployeeName = ref('')
const selectedRecord = ref(null)
const savingClearingOfficers = ref(false)
const clearingOfficersSaved = ref(false)

const formData = ref({
  employee_id: null,
  date_of_filing: '',
  date_of_effectivity: '',
  purpose_id: null,
  Other_purpose: '',
  is_other_mode: false,
  is_cleared: false,
  with_pending_administrative: false,
  with_ongoing_investigation: false,
  // Clearing officers (storing IDs for dropdowns, names cached separately)
  clearing_officer_supply_property: null,
  clearing_officer_hr_welfare: null,
  clearing_officer_union_cooperative: null,
  clearing_officer_library: null,
  clearing_officer_legal_library: null,
  clearing_officer_library_services: null,
  clearing_officer_financial_services: null,
  clearing_officer_transaction_billing: null,
  clearing_officer_payroll_remittance: null,
  clearing_officer_professional_development: null,
  clearing_officer_scholarship: null,
  clearing_officer_internal_affairs: null
})

const rules = {
  employee_id: [{ required: true, message: 'Employee is required', trigger: 'change' }],
  date_of_filing: [{ required: true, message: 'Date of filing is required', trigger: 'change' }],
  date_of_effectivity: [{ required: true, message: 'Date of effectivity is required', trigger: 'change' }],
  purpose_id: [
    {
      validator: (_, value, callback) => {
        if (formData.value.is_other_mode) return callback()
        if (!value) return callback(new Error('Purpose is required'))
        return callback()
      },
      trigger: 'change'
    }
  ],
  Other_purpose: [
    {
      validator: (_, value, callback) => {
        if (!showOtherPurpose.value) return callback()
        if (!value) return callback(new Error('Please specify the other mode of separation'))
        return callback()
      },
      trigger: 'blur'
    }
  ]
}

const formatEmployeeLabel = (emp) => {
  const parts = [emp.name]
  if (emp.position_name) parts.push(`- ${emp.position_name}`)
  return parts.join(' ')
}

const loadEmployees = async () => {
  try {
    const { data } = await clearanceCertificateApi.getEmployees()
    employees.value = data.data || []
  } catch (error) {
    console.error('Failed to load employees for clearance certificate', error)
    ElMessage.error('Failed to load employees')
  }
}

const showOtherPurpose = computed(() => {
  return formData.value.is_other_mode
})

const hasOtherPurpose = computed(() =>
  clearanceList.value.some(item => item.Other_purpose && item.Other_purpose !== '')
)

const loadPurposes = async () => {
  try {
    const { data } = await clearanceCertificateApi.getPurposes()
    purposes.value = data.data || []
  } catch (error) {
    console.error('Failed to load clearance purposes', error)
    ElMessage.error('Failed to load clearance purposes')
  }
}

const loadClearingOfficers = async () => {
  try {
    const { data } = await clearanceCertificateApi.getClearingOfficers()
    clearingOfficers.value = data.data || []
  } catch (error) {
    console.error('Failed to load clearing officers', error)
    ElMessage.error('Failed to load clearing officers')
  }
}

const formatClearingOfficerLabel = (officer) => {
  const parts = [officer.name]
  if (officer.position_name) parts.push(`- ${officer.position_name}`)
  return parts.join(' ')
}

// Load cached clearing officer selections from localStorage
// Note: We store names in cache but formData stores IDs, so we need to find IDs from names
const loadCachedClearingOfficers = () => {
  try {
    const cached = localStorage.getItem('clearance_clearing_officers')
    if (cached) {
      const cachedData = JSON.parse(cached)
      // Find officer IDs from cached names (matching by name only, not position)
      const findOfficerIdByName = (name) => {
        if (!name) return null
        // Match by name only, in case cached name doesn't include position
        const officer = clearingOfficers.value.find(o => o.name === name || formatClearingOfficerLabel(o) === name)
        return officer ? officer.id : null
      }
      
      // Update formData with IDs based on cached names
      formData.value.clearing_officer_supply_property = findOfficerIdByName(cachedData.clearing_officer_supply_property)
      formData.value.clearing_officer_hr_welfare = findOfficerIdByName(cachedData.clearing_officer_hr_welfare)
      formData.value.clearing_officer_union_cooperative = findOfficerIdByName(cachedData.clearing_officer_union_cooperative)
      formData.value.clearing_officer_library = findOfficerIdByName(cachedData.clearing_officer_library)
      formData.value.clearing_officer_legal_library = findOfficerIdByName(cachedData.clearing_officer_legal_library)
      formData.value.clearing_officer_library_services = findOfficerIdByName(cachedData.clearing_officer_library_services)
      formData.value.clearing_officer_financial_services = findOfficerIdByName(cachedData.clearing_officer_financial_services)
      formData.value.clearing_officer_transaction_billing = findOfficerIdByName(cachedData.clearing_officer_transaction_billing)
      formData.value.clearing_officer_payroll_remittance = findOfficerIdByName(cachedData.clearing_officer_payroll_remittance)
      formData.value.clearing_officer_professional_development = findOfficerIdByName(cachedData.clearing_officer_professional_development)
      formData.value.clearing_officer_scholarship = findOfficerIdByName(cachedData.clearing_officer_scholarship)
      formData.value.clearing_officer_internal_affairs = findOfficerIdByName(cachedData.clearing_officer_internal_affairs)
    }
  } catch (error) {
    console.error('Failed to load cached clearing officers', error)
  }
}

// Save clearing officer selections to localStorage
const saveClearingOfficersCache = () => {
  try {
    // Get names only (without position) from selected officer IDs
    const getOfficerName = (officerId) => {
      if (!officerId) return ''
      const officer = clearingOfficers.value.find(o => o.id === officerId)
      return officer ? officer.name : ''
    }
    
    const clearingOfficerData = {
      clearing_officer_supply_property: getOfficerName(formData.value.clearing_officer_supply_property),
      clearing_officer_hr_welfare: getOfficerName(formData.value.clearing_officer_hr_welfare),
      clearing_officer_union_cooperative: getOfficerName(formData.value.clearing_officer_union_cooperative),
      clearing_officer_library: getOfficerName(formData.value.clearing_officer_library),
      clearing_officer_legal_library: getOfficerName(formData.value.clearing_officer_legal_library),
      clearing_officer_library_services: getOfficerName(formData.value.clearing_officer_library_services),
      clearing_officer_financial_services: getOfficerName(formData.value.clearing_officer_financial_services),
      clearing_officer_transaction_billing: getOfficerName(formData.value.clearing_officer_transaction_billing),
      clearing_officer_payroll_remittance: getOfficerName(formData.value.clearing_officer_payroll_remittance),
      clearing_officer_professional_development: getOfficerName(formData.value.clearing_officer_professional_development),
      clearing_officer_scholarship: getOfficerName(formData.value.clearing_officer_scholarship),
      clearing_officer_internal_affairs: getOfficerName(formData.value.clearing_officer_internal_affairs)
    }
    localStorage.setItem('clearance_clearing_officers', JSON.stringify(clearingOfficerData))
    return true
  } catch (error) {
    console.error('Failed to save clearing officers cache', error)
    return false
  }
}

// Handle save clearing officers button click
const handleSaveClearingOfficers = async () => {
  savingClearingOfficers.value = true
  clearingOfficersSaved.value = false
  
  try {
    const success = saveClearingOfficersCache()
    if (success) {
      clearingOfficersSaved.value = true
      ElMessage.success('Clearing officers saved successfully')
      
      // Reset the saved state after 3 seconds
      setTimeout(() => {
        clearingOfficersSaved.value = false
      }, 3000)
    } else {
      ElMessage.error('Failed to save clearing officers')
    }
  } catch (error) {
    console.error('Failed to save clearing officers', error)
    ElMessage.error('Failed to save clearing officers')
  } finally {
    savingClearingOfficers.value = false
  }
}

// Get cached clearing officer names
const getCachedClearingOfficerNames = () => {
  try {
    const cached = localStorage.getItem('clearance_clearing_officers')
    if (cached) {
      return JSON.parse(cached)
    }
  } catch (error) {
    console.error('Failed to get cached clearing officers', error)
  }
  return {}
}

const loadClearances = async () => {
  loadingTable.value = true
  try {
    const { data } = await clearanceCertificateApi.list()
    clearanceList.value = data.data || []
  } catch (error) {
    console.error('Failed to load clearance certificates', error)
    ElMessage.error('Failed to load clearance certificates')
  } finally {
    loadingTable.value = false
  }
}

const onSubmit = () => {
  formRef.value?.validate(async (valid) => {
    if (!valid) return

    submitting.value = true
    try {
      const payload = {
        employee_id: formData.value.employee_id,
        Date_of_filing: formData.value.date_of_filing,
        Date_of_effectivity: formData.value.date_of_effectivity,
        purpose_id: formData.value.purpose_id,
        Other_purpose: formData.value.Other_purpose,
        is_cleared: formData.value.is_cleared,
        with_pending_administrative: formData.value.with_pending_administrative,
        with_ongoing_investigation: formData.value.with_ongoing_investigation
      }

      await clearanceCertificateApi.create(payload)
      ElMessage.success('Clearance certificate saved successfully')
      onReset()
      loadClearances()
    } catch (error) {
      console.error('Failed to save clearance certificate', error)
      ElMessage.error('Failed to save clearance certificate')
    } finally {
      submitting.value = false
    }
  })
}

const onReset = () => {
  formRef.value?.resetFields()
}

const handleOtherModeToggle = (val) => {
  if (val) {
    formData.value.purpose_id = null
  } else {
    formData.value.Other_purpose = ''
  }
}

const normalizeBool = (value) => {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  if (typeof value === 'string') return value === '1' || value.toLowerCase() === 'true'
  return !!value
}

const formatStatus = (row) => {
  const parts = []
  parts.push(normalizeBool(row.is_cleared) ? 'Cleared' : 'Not cleared')
  parts.push(normalizeBool(row.with_pending_administrative) ? 'With pending case' : 'No pending case')
  parts.push(normalizeBool(row.with_ongoing_investigation) ? 'With ongoing investigation' : 'No ongoing investigation')
  return parts.join(' • ')
}

const handlePrint = async (row) => {
  try {
    generateLoading.value = true
    selectedEmployeeName.value = row.employee_name || 'Unknown Employee'
    selectedRecord.value = row
    
    // Get clearing officer names from cache
    const clearingOfficerNames = getCachedClearingOfficerNames()
    
    // Build query parameters for clearing officer names
    const params = new URLSearchParams({ id: row.id })
    Object.keys(clearingOfficerNames).forEach(key => {
      if (clearingOfficerNames[key]) {
        params.append(key, clearingOfficerNames[key])
      }
    })
    
    // Generate PDF blob URL for preview
    const response = await clearanceCertificateApi.print(row.id, params.toString())
    const blob = new Blob([response.data], { type: 'application/pdf' })
    
    // Revoke old URL to prevent memory leaks
    if (previewPdfUrl.value) {
      URL.revokeObjectURL(previewPdfUrl.value)
    }
    
    previewPdfUrl.value = URL.createObjectURL(blob)
    previewKey.value++ // Force iframe refresh
    showPreviewModal.value = true
  } catch (error) {
    console.error('Failed to generate clearance certificate PDF', error)
    ElMessage.error('Failed to generate clearance certificate PDF')
  } finally {
    generateLoading.value = false
  }
}

const handleClosePreview = () => {
  showPreviewModal.value = false
  if (previewPdfUrl.value) {
    URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = ''
  }
}

const handleDownloadPdf = () => {
  if (previewPdfUrl.value) {
    const fileName = `clearance_certificate_${selectedEmployeeName.value?.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`
    
    // Convert blob URL to blob and download
    fetch(previewPdfUrl.value)
      .then(res => res.blob())
      .then(blob => {
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = fileName
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)
        ElMessage.success('PDF downloaded successfully')
      })
      .catch(error => {
        console.error('Download failed:', error)
        ElMessage.error('Failed to download PDF')
      })
  }
}

const handleDownloadWord = async () => {
  if (!selectedRecord.value?.id) {
    ElMessage.error('No clearance certificate selected')
    return
  }

  try {
    generateLoading.value = true
    const clearingOfficerNames = getCachedClearingOfficerNames()

    const params = new URLSearchParams({ id: selectedRecord.value.id })
    Object.keys(clearingOfficerNames).forEach(key => {
      if (clearingOfficerNames[key]) {
        params.append(key, clearingOfficerNames[key])
      }
    })

    const response = await clearanceCertificateApi.generateWord(selectedRecord.value.id, params.toString())
    const blob = new Blob(
      [response.data],
      { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' }
    )

    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    const fileName = `clearance_certificate_${selectedEmployeeName.value?.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.docx`
    a.href = url
    a.download = fileName
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
    ElMessage.success('Word document downloaded successfully')
  } catch (error) {
    console.error('Failed to download Word document', error)
    ElMessage.error('Failed to download Word document')
  } finally {
    generateLoading.value = false
  }
}

const handleDelete = async (row) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete the clearance certificate for ${row.employee_name}?`,
      'Confirm Delete',
      {
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }
    )

    try {
      console.log('Deleting clearance certificate with ID:', row.id)
      await clearanceCertificateApi.delete(row.id)
      ElMessage.success('Clearance certificate deleted successfully')
      loadClearances()
    } catch (error) {
      console.error('Failed to delete clearance certificate', error)
      const errorMessage = error.response?.data?.message || error.message || 'Failed to delete clearance certificate'
      ElMessage.error(errorMessage)
    }
  } catch (error) {
    // User cancelled the deletion
    if (error !== 'cancel') {
      console.error('Delete confirmation error', error)
    }
  }
}

onMounted(async () => {
  await loadEmployees()
  await loadClearances()
  await loadPurposes()
  await loadClearingOfficers()
  // Load cached clearing officers after clearing officers are loaded
  loadCachedClearingOfficers()
})
</script>

<style scoped>
.page-container {
  padding: 1.5rem 2rem;
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

.form-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem 2rem;
}

@media (min-width: 768px) {
  .form-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.form-item {
  margin-bottom: 0;
}

.status-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem 2rem;
  margin-top: 0.5rem;
}

@media (min-width: 768px) {
  .status-row {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.status-item {
  margin-bottom: 0;
}

.status-column {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.status-toggle-row {
  display: flex;
  align-items: center;
}

.status-label-left {
  width: 170px;
  text-align: right;
  margin-right: 8px;
  color: #409eff;
}

.status-label-right {
  margin-left: 8px;
  color: #111827;
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

.w-full {
  width: 100%;
}

.clearing-officers-container {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

.clearing-officer-card {
  border-radius: 8px;
  transition: all 0.3s ease;
}

.clearing-officer-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-header-content {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #409eff 0%, #66b1ff 100%);
  color: white;
  border-radius: 6px;
  font-weight: 600;
  font-size: 14px;
  flex-shrink: 0;
}

.card-title {
  font-weight: 600;
  font-size: 15px;
  color: #303133;
}

.clearing-officer-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.clearing-officer-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.item-label {
  font-size: 13px;
  color: #606266;
  font-weight: 500;
  line-height: 1.5;
}

.clearing-officer-select {
  width: 100%;
}

.clearing-officers-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 1.5rem;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
  border: 1px solid #e4e7ed;
}

.save-success-message {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #67c23a;
  font-size: 14px;
  font-weight: 500;
}

@media (min-width: 1200px) {
  .clearing-officers-container {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .clearing-officer-card:last-child {
    grid-column: 1 / -1;
  }
}
</style>