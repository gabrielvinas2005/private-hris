<template>
  <MainLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6">
      <!-- Authentication Required State -->
      <el-card v-if="!isAuthenticated && !loading" shadow="hover">
        <div class="text-center py-8">
          <el-icon size="48" color="#f59e0b" class="mb-4">
            <Lock />
          </el-icon>
          <h3 class="text-lg font-medium text-slate-900 mb-2">Authentication Required</h3>
          <p class="text-slate-600 mb-4">Please log in to access your 201 file.</p>
          <el-button type="primary" @click="redirectToLogin">
            <el-icon class="mr-2"><User /></el-icon>
            Go to Login
          </el-button>
        </div>
      </el-card>

      <!-- Loading State -->
      <el-card v-if="loading" shadow="hover">
        <div class="flex items-center justify-center py-8">
          <el-icon class="is-loading" size="32" color="#409EFF">
            <Loading />
          </el-icon>
          <span class="ml-3 text-slate-600">Loading employee data...</span>
        </div>
      </el-card>

      <!-- Error State -->
      <el-card v-else-if="error" shadow="hover">
        <div class="text-center py-8">
          <el-icon size="48" color="#f56565" class="mb-4">
            <Warning />
          </el-icon>
          <h3 class="text-lg font-medium text-slate-900 mb-2">Error Loading Data</h3>
          <p class="text-slate-600 mb-4">{{ error }}</p>
          <el-button type="primary" @click="retry">
            <el-icon class="mr-2"><Refresh /></el-icon>
            Try Again
          </el-button>
        </div>
      </el-card>

      <!-- Main Content -->
      <div v-else-if="isAuthenticated && employeeData.id">
        <el-card shadow="hover" class="mb-6">
          <template #header>
          <div class="flex justify-between items-start">
            <div>
              <h1 class="text-2xl font-bold text-slate-900">Employee 201 File</h1>
              <p class="text-slate-600 mt-1">Complete employee profile and records</p>
            </div>
            <div class="flex space-x-3">
                <el-button
                  v-if="canUpdate && !isEditMode"
                  type="primary"
                  @click="enterEditMode">
                  <el-icon class="mr-2"><Edit /></el-icon>
                Edit PDS
                </el-button>
                <el-button
                  v-if="isEditMode"
                  type="success"
                  @click="exitEditMode(true)">
                  <el-icon class="mr-2"><Check /></el-icon>
                Save & Exit
                </el-button>
                <el-button
                  v-if="isEditMode"
                  type="info"
                  @click="exitEditMode(false)">
                  <el-icon class="mr-2"><Close /></el-icon>
                Cancel
                </el-button>
                <el-button
                  type="warning"
                  @click="showPDSPrintPreview">
                  <el-icon class="mr-2"><Download /></el-icon>
                Download PDS
                </el-button>
              </div>
            </div>
          </template>
          
          <div class="mt-4">
            <el-alert
              v-if="canUpdate"
              :title="`${daysRemaining} day(s) before schedule of updating of 201 File closes.`"
              type="warning"
              :closable="false"
              class="mb-2" />
            <el-alert
              v-else
              title="Editing of 201 File is currently not available."
              type="error"
              :closable="false"
              class="mb-2" />
            <div class="text-sm text-slate-600">
              <el-icon class="mr-1"><Clock /></el-icon>
              Last Update: {{ lastUpdateDate }}
            </div>
          </div>
        </el-card>

        <!-- Print Preview -->
        <div v-if="showPrint" class="mb-6">
          <el-card shadow="never">
            <div class="flex items-center justify-between mb-3">
              <div class="text-base font-semibold">PDS Print Preview</div>
              <div class="flex items-center gap-2">
                <el-button size="small" type="primary" :disabled="!previewUrl" @click="downloadFromPreview">
                  Download
                </el-button>
                <el-button size="small" @click="closePrint"><el-icon><Close /></el-icon></el-button>
              </div>
            </div>
            <div v-if="previewUrl" class="border rounded overflow-hidden" style="height: 680px; max-width: 100%;">
              <iframe :src="previewUrl" class="w-full h-full" style="max-width: 100%;"></iframe>
            </div>
            <div v-else class="text-center text-gray-500 py-10">Loading preview...</div>
          </el-card>
        </div>

        <!-- Employee Profile Card -->
        <EmployeeProfileCard 
          :employee="employeeData" 
          :address="addressData"
          :is-edit-mode="isEditMode"
          :can-update="canUpdate"
          :gender-options="genderOptions"
          :blood-type-options="bloodTypeOptions"
          :civil-status-options="civilStatusOptions"
          :religion-options="religionOptions"
          :region-options="regionOptions"
          :province-options="provinceOptions"
          :city-options="cityOptions"
          :barangay-options="barangayOptions"
          :form-data="editFormData"
          @update:form-data="updateFormData"
          class="mb-6"
        />

        <!-- Main Content Tabs -->
        <el-card shadow="hover">
          <el-tabs v-model="activeTab" type="border-card" class="employee-tabs">
            <el-tab-pane
                v-for="tab in tabs"
                :key="tab.id"
              :label="tab.name"
              :name="tab.id">
              
              <!-- Work Information Tab -->
              <WorkInformation 
                v-if="activeTab === 'work'" 
                :work-info="workInfo" 
                :payroll-info="payrollInfo" 
                :incomes="incomes" 
                :loans="loans"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Family Information Tab -->
              <FamilyInformation 
                v-if="activeTab === 'family'" 
                :family-info="familyInfo" 
                :children="children"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Education Information Tab -->
              <EducationInformation 
                v-if="activeTab === 'education'" 
                :educations="educations"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Service Record Tab -->
              <ServiceRecord 
                v-if="activeTab === 'service'" 
                :service-records="serviceRecords"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                :employment-type-options="employmentTypeOptions || []"
                @update:form-data="updateFormData" />
              
              <!-- Work Experience Tab -->
              <WorkExperience 
                v-if="activeTab === 'experience'" 
                :work-experiences="workExperiences"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Eligibility Information Tab -->
              <EligibilityInformation 
                v-if="activeTab === 'eligibility'" 
                :eligibilities="eligibilities"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Training Information Tab -->
              <TrainingInformation 
                v-if="activeTab === 'training'" 
                :trainings="trainings"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Voluntary Work Tab -->
              <VoluntaryWork 
                v-if="activeTab === 'voluntary'" 
                :voluntary-works="voluntaryWorks"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- IPCR Results Tab -->
              <IPCRResults 
                v-if="activeTab === 'ipcr'" 
                :ipcr-results="ipcrResults"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
              
              <!-- Other Information Tab -->
              <OtherInformation 
                v-if="activeTab === 'other'" 
                :recognitions="recognitions" 
                :skills="skills" 
                :memberships="memberships" 
                :references="references"
                :is-edit-mode="isEditMode"
                :can-update="canUpdate"
                :form-data="editFormData"
                @update:form-data="updateFormData" />
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </div>

    </div>
  </MainLayout>
</template>

<script>
import { onMounted, onUnmounted, computed, ref } from 'vue'
import MainLayout from '../../layout/MainLayout.vue'
import EmployeeProfileCard from '../../components/201/EmployeeProfileCard.vue'
import WorkInformation from '../../components/201/WorkInformation.vue'
import FamilyInformation from '../../components/201/FamilyInformation.vue'
import EducationInformation from '../../components/201/EducationInformation.vue'
import ServiceRecord from '../../components/201/ServiceRecord.vue'
import WorkExperience from '../../components/201/WorkExperience.vue'
import EligibilityInformation from '../../components/201/EligibilityInformation.vue'
import TrainingInformation from '../../components/201/TrainingInformation.vue'
import VoluntaryWork from '../../components/201/VoluntaryWork.vue'
import IPCRResults from '../../components/201/IPCRResults.vue'
import OtherInformation from '../../components/201/OtherInformation.vue'
import { useEmployee201File } from '../../composables/useEmployee201File.js'
import { usePDSEdit } from '../../composables/usePDSEdit.js'
import ApiService from '../../services/api.js'
import { Edit, Check, Close, Download, Clock, Loading } from '@element-plus/icons-vue'

export default {
  name: 'Employee201File',
  components: {
    MainLayout,
    EmployeeProfileCard,
    WorkInformation,
    FamilyInformation,
    EducationInformation,
    ServiceRecord,
    WorkExperience,
    EligibilityInformation,
    TrainingInformation,
    VoluntaryWork,
    IPCRResults,
    OtherInformation,
    Edit,
    Check,
    Close,
    Download,
    Clock,
    Loading
  },
  setup() {
    const employee201File = useEmployee201File()
    
    // Destructure everything except refs that need to stay reactive
    const {
      // State
      loading,
      error,
      isAuthenticated,
      activeTab,
      userData,
      employeeData,
      addressData,
      workInfo,
      payrollInfo,
      familyInfo,
      incomes,
      loans,
      children,
      educations,
      serviceRecords,
      workExperiences,
      eligibilities,
      trainings,
      voluntaryWorks,
      ipcrResults,
      recognitions,
      skills,
      memberships,
      references,
      genderOptions,
      bloodTypeOptions,
      civilStatusOptions,
      religionOptions,
      regionOptions,
      provinceOptions,
      cityOptions,
      barangayOptions,
      canUpdate,
      daysRemaining,
      lastUpdateDate,
      
      // Computed
      breadcrumbs,
      tabs,
      
      // Methods
      checkAuthentication,
      redirectToLogin,
      retry,
      loadEmployeeData,
      downloadPDS,
      setActiveTab
    } = employee201File
    
    // Keep employmentTypeOptions as a ref to maintain reactivity
    const employmentTypeOptions = employee201File.employmentTypeOptions

    // PDS Edit functionality
    const employeeId = computed(() => employeeData.id || userData.id)

    // PDS Print Preview state
    const showPrint = ref(false)
    const previewUrl = ref(null)
    const previewLoading = ref(false)
    const previewFileName = ref(null)

    const closePrint = () => {
      showPrint.value = false
      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
        previewUrl.value = null
      }
      previewFileName.value = null
    }

    const downloadFromPreview = () => {
      if (!previewUrl.value) return
      const a = document.createElement('a')
      a.href = previewUrl.value
      a.download = previewFileName.value || `PDS_${employeeId.value}.pdf`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    }

    const showPDSPrintPreview = async () => {
      if (!employeeId.value) return
      try {
        showPrint.value = true
        previewLoading.value = true
        previewFileName.value = `PDS_${employeeId.value}.pdf`

        if (previewUrl.value) {
          URL.revokeObjectURL(previewUrl.value)
          previewUrl.value = null
        }

        await ApiService.initSanctum()
        const url = `${ApiService.baseURL}/pds/${employeeId.value}/download`

        const token = localStorage.getItem('auth_token')
        const xsrfCookie = typeof document !== 'undefined'
          ? (document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1]
          : null

        const headers = {
          'Accept': 'application/pdf',
          'X-Requested-With': 'XMLHttpRequest',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
          ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie) } : {})
        }

        const res = await fetch(url, { method: 'GET', headers, credentials: 'include' })
        if (!res.ok) {
          const text = await res.text().catch(() => '')
          throw new Error(`HTTP error! status: ${res.status}${text ? ` - ${text}` : ''}`)
        }
        const blob = await res.blob()
        previewUrl.value = URL.createObjectURL(blob)
      } catch (e) {
        console.error('PDS print preview failed:', e)
        closePrint()
      } finally {
        previewLoading.value = false
      }
    }

    const {
      isEditMode,
      isSaving,
      lastSaveTime,
      editFormData,
      enterEditMode: enterEdit,
      exitEditMode: exitEdit,
      updateFormData,
      cleanup
    } = usePDSEdit(employeeId)

    const enterEditMode = () => {
      enterEdit(
        employeeData, 
        addressData, 
        workInfo, 
        payrollInfo, 
        familyInfo,
        children.value,
        educations.value,
        serviceRecords.value,
        workExperiences.value,
        eligibilities.value,
        trainings.value,
        voluntaryWorks.value,
        recognitions.value,
        skills.value,
        memberships.value,
        references.value
      )
    }

    const exitEditMode = (save = false) => {
      exitEdit(save).then(() => {
        if (save) {
          loadEmployeeData() // Reload data after saving
        }
      })
    }

    onUnmounted(() => {
      cleanup()
      closePrint()
    })

    onMounted(() => {
      if (checkAuthentication()) {
        loadEmployeeData()
      }
    })

    return {
      // State
      loading,
      error,
      isAuthenticated,
      activeTab,
      userData,
      employeeData,
      addressData,
      workInfo,
      payrollInfo,
      familyInfo,
      incomes,
      loans,
      children,
      educations,
      serviceRecords,
      workExperiences,
      eligibilities,
      trainings,
      voluntaryWorks,
      ipcrResults,
      recognitions,
      skills,
      memberships,
      references,
      canUpdate,
      daysRemaining,
      lastUpdateDate,
      genderOptions,
      bloodTypeOptions,
      civilStatusOptions,
      religionOptions,
      employmentTypeOptions: employmentTypeOptions, // Keep as ref for reactivity
      regionOptions,
      provinceOptions,
      cityOptions,
      barangayOptions,
      
      // Edit mode
      isEditMode,
      isSaving,
      lastSaveTime,
      editFormData,
      
      // Computed
      breadcrumbs,
      tabs,
      
      // Methods
      redirectToLogin,
      retry,
      downloadPDS,
      showPDSPrintPreview,
      closePrint,
      downloadFromPreview,
      enterEditMode,
      exitEditMode,
      updateFormData,

      // Print preview
      showPrint,
      previewUrl,
      previewLoading,
      
      // Icons
      Edit,
      Check,
      Close,
      Download,
      Clock,
      Loading
    }
  }
}
</script>

<style scoped>
/* Custom styles for Element Plus components */
.employee-tabs {
  border: none;
}

.employee-tabs :deep(.el-tabs__header) {
  margin: 0;
  border-bottom: 1px solid #e4e7ed;
}

.employee-tabs :deep(.el-tabs__nav-wrap) {
  padding: 0 20px;
}

.employee-tabs :deep(.el-tabs__item) {
  padding: 0 20px;
  height: 50px;
  line-height: 50px;
  font-weight: 500;
}

.employee-tabs :deep(.el-tabs__item.is-active) {
  color: #409eff;
  border-bottom-color: #409eff;
}

.employee-tabs :deep(.el-tabs__content) {
  padding: 20px;
}

/* Custom spacing for cards */
.mb-6 {
  margin-bottom: 1.5rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.mr-1 {
  margin-right: 0.25rem;
}

.mr-2 {
  margin-right: 0.5rem;
}

.ml-3 {
  margin-left: 0.75rem;
}

/* Flex utilities */
.flex {
  display: flex;
}

.items-center {
  align-items: center;
}

.justify-center {
  justify-content: center;
}

.justify-between {
  justify-content: space-between;
}

.items-start {
  align-items: flex-start;
}

.space-x-3 > * + * {
  margin-left: 0.75rem;
}

/* Text utilities */
.text-center {
  text-align: center;
}

.text-sm {
  font-size: 0.875rem;
  line-height: 1.25rem;
}

.text-lg {
  font-size: 1.125rem;
  line-height: 1.75rem;
}

.text-2xl {
  font-size: 1.5rem;
  line-height: 2rem;
}

.font-medium {
  font-weight: 500;
}

.font-bold {
  font-weight: 700;
}

.text-slate-900 {
  color: #0f172a;
}

.text-slate-600 {
  color: #475569;
}

/* Padding utilities */
.py-8 {
  padding-top: 2rem;
  padding-bottom: 2rem;
}

.px-4 {
  padding-left: 1rem;
  padding-right: 1rem;
}

.py-2 {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.px-6 {
  padding-left: 1.5rem;
  padding-right: 1.5rem;
}

.py-4 {
  padding-top: 1rem;
  padding-bottom: 1rem;
}

/* Margin utilities */
.mt-1 {
  margin-top: 0.25rem;
}

.mt-4 {
  margin-top: 1rem;
}

.mb-4 {
  margin-bottom: 1rem;
}

/* Loading animation */
.is-loading {
  animation: rotating 2s linear infinite;
}

@keyframes rotating {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
