<template>
  <PageScaffold
    title="Hazard Pay"
    subtitle="Manage hazard pay allowances for employees working in hazardous conditions"
    :breadcrumbs="[{ label: 'Payroll Module', to: '/' }, { label: 'Payroll Benefits', to: '/payroll-benefits' }, { label: 'Hazard Pay' }]"
  >
    <template #actions>
      <el-button 
        type="primary" 
        @click="handleCreate" 
        :loading="loading"
        icon="el-icon-plus"
        size="default"
      >
        Add Hazard Pay
      </el-button>
      <el-button 
        type="success" 
        @click="showReportDialog = true" 
        :loading="loading"
        icon="el-icon-printer"
        size="default"
      >
        Generate Report
      </el-button>
      <el-button 
        type="info" 
        @click="showSetupDialog = true" 
        :loading="loading"
        icon="el-icon-setting"
        size="default"
      >
        Setup
      </el-button>
      <el-button 
        type="info" 
        @click="loadHazardPayData" 
        :loading="loading"
        icon="el-icon-refresh"
        size="default"
      >
        Refresh
      </el-button>
    </template>
    
    <!-- Enhanced Filters -->
    <div class="filters-container">
      <el-form :inline="true" class="enhanced-filters">
        <el-form-item label="Search">
          <el-input 
            v-model="searchQuery" 
            placeholder="Search by department or month" 
            clearable 
            style="width: 280px"
            @input="handleSearch"
            prefix-icon="el-icon-search"
          />
        </el-form-item>
        <el-form-item label="Status">
          <el-select 
            v-model="statusFilter" 
            placeholder="All Status" 
            style="width: 140px" 
            @change="handleFilter"
            clearable
            class="status-select"
          >
            <el-option label="All" value="" />
            <el-option label="Posted" value="posted">
              <el-tag type="success" size="small">Posted</el-tag>
            </el-option>
            <el-option label="Draft" value="draft">
              <el-tag type="warning" size="small">Draft</el-tag>
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button 
            type="primary" 
            @click="handleFilter"
            icon="el-icon-search"
            class="filter-btn"
          >
            Filter
          </el-button>
          <el-button 
            @click="resetFilters"
            icon="el-icon-refresh-left"
            class="reset-btn"
          >
            Reset
          </el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Hazard Pay List -->
    <HazardPayList
      ref="listRef"
      :hazard-pay-data="filteredHazardPayData"
      :loading="loading"
      @edit="handleEdit"
      @view="handleView"
      @post="handlePost"
      @unpost="handleUnpost"
      @delete="handleDelete"
    />

    <!-- Create/Edit Form Dialog -->
    <HazardPayForm
      v-model="showFormDialog"
      :hazard-pay-id="selectedHazardPayId"
      :edit-data="editData"
      @saved="handleFormSaved"
      @close="handleFormClose"
    />

    <!-- View Dialog -->
    <HazardPayView
      v-model="showViewDialog"
      :hazard-pay-id="selectedHazardPayId"
      @close="handleViewClose"
    />

    <!-- Report Generation Dialog -->
    <HazardPayReports
      v-model="showReportDialog"
      @report-generated="handleReportGenerated"
      @close="handleReportClose"
    />

    <!-- Setup Dialog -->
    <HazardPaySetup
      v-model="showSetupDialog"
      @close="handleSetupClose"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import PageScaffold from '../../../components/PageScaffold.vue'
import HazardPayList from '../../../components/Payroll_Benefits/Hazard_Pay/HazardPayList.vue'
import HazardPayForm from '../../../components/Payroll_Benefits/Hazard_Pay/HazardPayForm.vue'
import HazardPayReports from '../../../components/Payroll_Benefits/Hazard_Pay/HazardPayReports.vue'
import HazardPayView from '../../../components/Payroll_Benefits/Hazard_Pay/HazardPayView.vue'
import HazardPaySetup from '../../../components/Payroll_Benefits/Hazard_Pay/HazardPaySetup.vue'
import { useHazardPay } from '../../../Composables/useHazardPay.js'

// Composables
const { 
  loading, 
  hazardPayList, 
  loadHazardPayList, 
  processHazardPay,
  transformHazardPayData
} = useHazardPay()

// Local state
const showFormDialog = ref(false)
const showViewDialog = ref(false)
const showReportDialog = ref(false)
const showSetupDialog = ref(false)
const selectedHazardPayId = ref(0)
const editData = ref(null)
const searchQuery = ref('')
const statusFilter = ref('')
const listRef = ref(null)

// Computed properties
const filteredHazardPayData = computed(() => {
  let filtered = hazardPayList.value

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(item => 
      item.department?.toLowerCase().includes(query) ||
      item.month?.toLowerCase().includes(query) ||
      item.year?.toString().includes(query)
    )
  }

  // Apply status filter
  if (statusFilter.value) {
    const isPosted = statusFilter.value === 'posted'
    filtered = filtered.filter(item => item.posted === isPosted)
  }

  return transformHazardPayData(filtered)
})

// Methods
const loadHazardPayData = async () => {
  try {
    await loadHazardPayList()
  } catch (error) {
    console.error('Error loading hazard pay data:', error)
  }
}

const handleSearch = () => {
  // Search is handled by computed property
}

const handleFilter = () => {
  // Filtering is handled by computed property
}

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
}

const handleCreate = () => {
  selectedHazardPayId.value = 0
  editData.value = null
  showFormDialog.value = true
}

const handleEdit = (hazardPay) => {
  selectedHazardPayId.value = hazardPay.id
  editData.value = hazardPay
  showFormDialog.value = true
}

const handleView = (hazardPay) => {
  selectedHazardPayId.value = hazardPay.id
  showViewDialog.value = true
}

const handlePost = async (hazardPay) => {
  try {
    await processHazardPay(hazardPay.id, 1)
    await loadHazardPayData() // Refresh the list
  } catch (error) {
    console.error('Error posting hazard pay:', error)
  }
}

const handleUnpost = async (hazardPay) => {
  try {
    await processHazardPay(hazardPay.id, 0)
    await loadHazardPayData() // Refresh the list
  } catch (error) {
    console.error('Error unposting hazard pay:', error)
  }
}

const handleDelete = async (hazardPay) => {
  try {
    // TODO: Implement delete functionality
    console.log('Delete hazard pay:', hazardPay)
    await loadHazardPayData() // Refresh the list
  } catch (error) {
    console.error('Error deleting hazard pay:', error)
  }
}

const handleFormSaved = () => {
  loadHazardPayData() // Refresh the list
}

const handleFormClose = () => {
  selectedHazardPayId.value = 0
  editData.value = null
}

const handleViewClose = () => {
  selectedHazardPayId.value = 0
}

const handleReportGenerated = () => {
  // Report was generated successfully
}

const handleReportClose = () => {
  // Report dialog was closed
}

const handleSetupClose = () => {
  // Setup dialog was closed
}

onMounted(() => {
  loadHazardPayData()
})
</script>

<style scoped>
/* Filters Container */
.filters-container {
  background: #ffffff;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e5e7eb;
}

.enhanced-filters {
  margin: 0;
}

.enhanced-filters :deep(.el-form-item) {
  margin-right: 20px;
  margin-bottom: 0;
}

.enhanced-filters :deep(.el-form-item__label) {
  font-weight: 600;
  color: #374151;
  font-size: 14px;
}

/* Enhanced Select Styling */
.status-select :deep(.el-input__inner) {
  border-radius: 8px;
  border: 1px solid #d1d5db;
  transition: all 0.2s ease;
  font-size: 14px;
}

.status-select :deep(.el-input__inner:focus) {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Enhanced Button Styling */
.filter-btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 10px 20px;
  transition: all 0.2s ease;
}

.filter-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
}

.reset-btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 10px 20px;
  border: 1px solid #d1d5db;
  color: #6b7280;
  transition: all 0.2s ease;
}

.reset-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-color: #9ca3af;
  color: #374151;
}

/* Utility Classes */
.mb-3 { 
  margin-bottom: 12px; 
}

/* Responsive Design */
@media (max-width: 768px) {
  .filters-container {
    padding: 16px;
  }
  
  .enhanced-filters :deep(.el-form-item) {
    margin-right: 0;
    margin-bottom: 16px;
    width: 100%;
  }
  
  .search-input {
    width: 100% !important;
  }
  
  .status-select {
    width: 100% !important;
  }
  
  .enhanced-filters :deep(.el-form-item__content) {
    width: 100%;
  }
}

/* Animation for filter container */
.filters-container {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>