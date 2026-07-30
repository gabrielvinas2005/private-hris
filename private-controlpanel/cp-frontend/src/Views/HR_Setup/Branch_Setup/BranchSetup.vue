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
        </div>
      </div>
    </template>

    <div class="branch-setup-container">
      <!-- Filters -->
      <BranchFilters
        v-model="filters"
        @change="handleFiltersChange"
      />

      <!-- Branch Table -->
      <BranchTable
        :branches="branches"
        :employees="employees"
        :loading="loading"
        :deleting="deleting"
        :deleting-id="deletingId"
        :search-term="filters.search"
        :type-filter="filters.type"
        @add="handleAddBranch"
        @edit="handleEditBranch"
        @delete="handleDeleteBranch"
      />

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
  OfficeBuilding
} from '@element-plus/icons-vue'
import MainLayout from '../../../Layout/MainLayout.vue'
import BranchFilters from '../../../components/HR_Setup/Branch_Setup/BranchFilters.vue'
import BranchTable from '../../../components/HR_Setup/Branch_Setup/BranchTable.vue'
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
  fetchEmployees,
  saveBranches,
  deleteBranch,
  confirmDeleteBranch,
  formatBranchData
} = useBranch()

// Reactive state
const showModal = ref(false)
const selectedBranch = ref({})
const showSuccessMessage = ref(false)
const successMessage = ref('')
const deletingId = ref(null)
const filters = ref({
  search: '',
  type: ''
})

// Methods
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
    deletingId.value = branch.id
    const result = await deleteBranch(branch.id)
    if (result.success) {
      showSuccessMessage.value = true
      successMessage.value = `Branch "${branch.name}" deleted successfully!`
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 5000)
    }
    deletingId.value = null
  }
}

async function handleSubmitBranch(branchData) {
  try {
    // Format data for backend (array format)
    const formattedData = formatBranchData([branchData])
    const result = await saveBranches(formattedData)
    
    if (result.success) {
      await fetchBranches()
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

function handleFiltersChange(newFilters) {
  filters.value = { ...newFilters }
}

// Initialize data
onMounted(async () => {
  // Fetch branches (which includes employees in the response)
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


.branch-setup-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 16px;
}

.success-alert {
  margin-bottom: 24px;
  border-radius: 8px;
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
  
  .branch-setup-container {
    padding: 0 8px;
  }
}
</style>


