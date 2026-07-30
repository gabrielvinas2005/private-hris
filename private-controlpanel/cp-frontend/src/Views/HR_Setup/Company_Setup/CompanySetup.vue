<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <el-icon class="header-icon"><OfficeBuilding /></el-icon>
            <div class="header-text">
              <h1 class="page-title">Company Setup</h1>
              <p class="page-description">Manage your company information and settings</p>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div class="company-setup-container">
      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="8" animated />
      </div>

      <!-- Company Information View -->
      <div v-else-if="!showForm" class="view-mode">
        <CompanyInfo
          :company="primaryCompany"
          :loading="loading"
          @edit="showForm = true"
          @add="showForm = true"
        />
      </div>

      <!-- Company Form -->
      <div v-else class="form-mode">
        <el-card class="form-card" shadow="never">
          <template #header>
            <div class="form-header">
              <el-icon><Edit /></el-icon>
              <span>{{ hasCompany ? 'Edit Company Information' : 'Add Company Information' }}</span>
            </div>
          </template>

          <CompanyForm
            :company-data="primaryCompany"
            @saved="handleCompanySaved"
            @reset="handleFormReset"
          />
        </el-card>
      </div>

      <!-- Success Message -->
      <el-alert
        v-if="showSuccessMessage"
        title="Company information updated successfully!"
        type="success"
        :closable="false"
        show-icon
        class="success-alert"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { 
  OfficeBuilding,
  Edit
} from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import CompanyInfo from '../../../components/HR_Setup/Company_Setup/CompanyInfo.vue'
import CompanyForm from '../../../components/HR_Setup/Company_Setup/CompanyForm.vue'
import { useCompany } from '../../../composables/useCompany.js'

// Composables
const { 
  companies, 
  primaryCompany, 
  hasCompany, 
  loading, 
  fetchCompanies 
} = useCompany()

// Reactive state
const showForm = ref(false)
const showSuccessMessage = ref(false)

// Methods
async function handleCompanySaved(savedData) {
  showForm.value = false
  showSuccessMessage.value = true
  
  // Refresh company data after saving
  await fetchCompanies()
  
  // Hide success message after 5 seconds
  setTimeout(() => {
    showSuccessMessage.value = false
  }, 5000)
}

function handleFormReset() {
  // Form reset handled by the form component
  ElMessage.info('Form has been reset')
}

// Initialize data
onMounted(async () => {
  await fetchCompanies()
})
</script>

<style scoped>
.page-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px 32px;
  border-radius: 12px;
  margin-bottom: 24px;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  font-size: 32px;
  color: rgba(255, 255, 255, 0.9);
}

.header-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.page-title {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: white;
}

.page-description {
  margin: 0;
  font-size: 16px;
  color: rgba(255, 255, 255, 0.8);
  font-weight: 400;
}


.company-setup-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.loading-container {
  padding: 40px 0;
}

.view-mode {
  animation: fadeIn 0.3s ease-in-out;
}

.form-mode {
  animation: fadeIn 0.3s ease-in-out;
}

.form-card {
  border-radius: 12px;
  border: 1px solid #e4e7ed;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}

.form-header {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.form-header .el-icon {
  font-size: 20px;
  color: #409eff;
}

.success-alert {
  margin-bottom: 24px;
  border-radius: 8px;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

:deep(.el-card__header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #e4e7ed;
  padding: 20px 24px;
}

:deep(.el-card__body) {
  padding: 24px;
}

:deep(.el-skeleton) {
  padding: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header {
    padding: 20px 16px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: flex-start;
  }
  
  .page-title {
    font-size: 24px;
  }
  
  .page-description {
    font-size: 14px;
  }
  
  .company-setup-container {
    padding: 0 8px;
  }
}
</style>


