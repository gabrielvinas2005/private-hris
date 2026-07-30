<template>
  <MainLayout>
    <template #header>
      <div class="page-header">
        <div class="header-content">
          <div class="header-left">
            <el-icon class="header-icon"><OfficeBuilding /></el-icon>
            <div class="header-text">
              <h1 class="page-title">Branch Setup</h1>
              <p class="page-description">Manage company branches and their configurations</p>
            </div>
          </div>
          <div class="header-actions">
            <el-button
              type="primary"
              :icon="Plus"
              @click="handleAddBranch"
              :loading="loading"
            >
              Add Branch
            </el-button>
          </div>
        </div>
      </div>
    </template>

    <div class="branch-setup-container">
      <!-- Simple Data Display -->
      <el-card class="data-card" shadow="never">
        <template #header>
          <div class="card-header">
            <el-icon><OfficeBuilding /></el-icon>
            <span>Branches Data</span>
            <el-button size="small" @click="refreshData" :loading="loading">
              Refresh
            </el-button>
          </div>
        </template>

        <div v-if="loading" class="loading-container">
          <el-skeleton :rows="5" animated />
        </div>

        <div v-else-if="!hasBranches" class="no-data">
          <el-empty description="No branches found">
            <el-button type="primary" @click="handleAddBranch">
              Add First Branch
            </el-button>
          </el-empty>
        </div>

        <div v-else class="data-display">
          <div class="data-summary">
            <el-tag type="info" size="large">
              Total Branches: {{ branches.length }}
            </el-tag>
            <el-tag type="success" size="large">
              Main Branches: {{ mainBranches.length }}
            </el-tag>
            <el-tag type="warning" size="large">
              Regular Branches: {{ regularBranches.length }}
            </el-tag>
          </div>

          <div class="branches-list">
            <div 
              v-for="branch in branches" 
              :key="branch.id"
              class="branch-item"
            >
              <div class="branch-info">
                <div class="branch-name">
                  <span class="name">{{ branch.name }}</span>
                  <el-tag v-if="branch.is_main_branch" type="success" size="small">
                    Main Branch
                  </el-tag>
                </div>
                <div class="branch-details">
                  <span v-if="branch.code" class="code">Code: {{ branch.code }}</span>
                  <span class="head">Head: {{ getBranchHeadName(branch) }}</span>
                </div>
              </div>
              <div class="branch-actions">
                <el-button size="small" type="primary" @click="handleEditBranch(branch)">
                  Edit
                </el-button>
                <el-button size="small" type="danger" @click="handleDeleteBranch(branch)">
                  Delete
                </el-button>
              </div>
            </div>
          </div>
        </div>
      </el-card>

      <!-- Branch Modal -->
      <BranchModal
        v-model="showModal"
        :branch-data="selectedBranch"
        :employees="employees"
        :saving="saving"
        @submit="handleSubmitBranch"
        @close="handleCloseModal"
      />

      <!-- Success Message -->
      <el-alert
        v-if="showSuccessMessage"
        :title="successMessage"
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
  Plus 
} from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import BranchModal from '../../../components/HR_Setup/Branch_Setup/BranchModal.vue'
import { useBranch } from '../../../composables/useBranch.js'

// Composables
const { 
  branches, 
  employees, 
  loading, 
  saving, 
  deleting,
  fetchBranches, 
  saveBranches,
  deleteBranch,
  confirmDeleteBranch,
  formatBranchData,
  getBranchHeadName
} = useBranch()

// Computed
const hasBranches = computed(() => branches.value.length > 0)
const mainBranches = computed(() => branches.value.filter(branch => branch.is_main_branch))
const regularBranches = computed(() => branches.value.filter(branch => !branch.is_main_branch))

// Reactive state
const showModal = ref(false)
const selectedBranch = ref({})
const showSuccessMessage = ref(false)
const successMessage = ref('')

// Methods
async function refreshData() {
  await fetchBranches()
}

async function handleAddBranch() {
  selectedBranch.value = {}
  showModal.value = true
}

async function handleEditBranch(branch) {
  selectedBranch.value = { ...branch }
  showModal.value = true
}

async function handleDeleteBranch(branch) {
  const confirmed = await confirmDeleteBranch(branch)
  if (confirmed) {
    const result = await deleteBranch(branch.id)
    if (result.success) {
      showSuccessMessage.value = true
      successMessage.value = `Branch "${branch.name}" deleted successfully!`
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 5000)
    }
  }
}

async function handleSubmitBranch(branchData) {
  try {
    const formattedData = formatBranchData([branchData])
    const result = await saveBranches(formattedData)
    
    if (result.success) {
      showModal.value = false
      showSuccessMessage.value = true
      successMessage.value = branchData.id 
        ? `Branch "${branchData.name}" updated successfully!`
        : `Branch "${branchData.name}" added successfully!`
      
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 5000)
    }
  } catch (error) {
    console.error('Error submitting branch:', error)
  }
}

function handleCloseModal() {
  showModal.value = false
  selectedBranch.value = {}
}

// Initialize data
onMounted(async () => {
  await fetchBranches()
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

.header-actions {
  display: flex;
  gap: 12px;
}

.branch-setup-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

.data-card {
  border-radius: 12px;
  border: 1px solid #e4e7ed;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
  color: #303133;
}

.card-header .el-icon {
  font-size: 18px;
  color: #409eff;
}

.loading-container {
  padding: 20px 0;
}

.no-data {
  padding: 40px 0;
}

.data-display {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.data-summary {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.branches-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.branch-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  background-color: #fafafa;
}

.branch-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.branch-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.name {
  font-weight: 600;
  color: #303133;
  font-size: 16px;
}

.branch-details {
  display: flex;
  gap: 16px;
  font-size: 14px;
  color: #606266;
}

.code {
  font-weight: 500;
}

.branch-actions {
  display: flex;
  gap: 8px;
}

.success-alert {
  margin-bottom: 24px;
  border-radius: 8px;
}

:deep(.el-card__header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #e4e7ed;
  padding: 20px 24px;
}

:deep(.el-card__body) {
  padding: 24px;
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
  
  .header-actions {
    width: 100%;
    justify-content: flex-end;
  }
  
  .page-title {
    font-size: 24px;
  }
  
  .page-description {
    font-size: 14px;
  }
  
  .branch-setup-container {
    padding: 0 8px;
  }
  
  .branch-item {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
  
  .branch-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
