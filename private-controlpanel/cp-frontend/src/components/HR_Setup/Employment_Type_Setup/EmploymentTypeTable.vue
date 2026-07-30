<template>
  <div class="employment-type-table">

    <el-table
      :data="filteredEmploymentTypes"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No employment types found"
      ref="tableRef"
    >
      <el-table-column 
        v-if="visible.id" 
        prop="id" 
        label="ID" 
        width="80" 
        align="center"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.name" 
        prop="name" 
        label="Employment Type Name" 
        min-width="250"
        align="left"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.with_end_contract" 
        prop="with_end_contract" 
        label="End Contract" 
        width="120" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.with_end_contract ? 'warning' : 'info'" size="small">
            {{ row.with_end_contract ? 'Yes' : 'No' }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.status" 
        prop="active" 
        label="Status" 
        width="100" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.active ? 'success' : 'danger'" size="small">
            {{ row.active ? 'Active' : 'Inactive' }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.actions" 
        label="Actions" 
        width="150" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="$emit('edit', row)"
            :loading="loading"
          >
            Edit
          </el-button>
          <el-button
            type="danger"
            size="small"
            @click="$emit('delete', row)"
            :loading="loading"
          >
            Delete
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- Pagination -->
    <div class="pagination-container" v-if="totalItems > pageSize">
      <el-pagination
        v-model:current-page="currentPage"
        :page-size="pageSize"
        :total="totalItems"
        layout="total, prev, pager, next, jumper"
        @current-change="handlePageChange"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  employmentTypes: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  searchTerm: {
    type: String,
    default: ''
  },
  statusFilter: {
    type: String,
    default: ''
  },
  visible: {
    type: Object,
    default: () => ({
      id: true,
      name: true,
      with_end_contract: true,
      status: true,
      actions: true
    })
  }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'page-change'])

// Refs
const tableRef = ref(null)

// State
const currentPage = ref(1)
const pageSize = ref(10)

// Computed
const filteredEmploymentTypes = computed(() => {
  let filtered = props.employmentTypes.filter(employmentType => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        employmentType.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !employmentType.active) return false
      if (props.statusFilter === 'inactive' && employmentType.active) return false
      if (props.statusFilter === 'with_contract' && !employmentType.with_end_contract) return false
      if (props.statusFilter === 'without_contract' && employmentType.with_end_contract) return false
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.employmentTypes.filter(employmentType => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        employmentType.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !employmentType.active) return false
      if (props.statusFilter === 'inactive' && employmentType.active) return false
      if (props.statusFilter === 'with_contract' && !employmentType.with_end_contract) return false
      if (props.statusFilter === 'without_contract' && employmentType.with_end_contract) return false
    }

    return true
  })

  return filtered.length
})

// Methods
const handlePageChange = (page) => {
  currentPage.value = page
  emit('page-change', page)
}

// Expose method to get all filtered data (without pagination)
const getFilteredData = () => {
  return props.employmentTypes.filter(employmentType => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        employmentType.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !employmentType.active) return false
      if (props.statusFilter === 'inactive' && employmentType.active) return false
      if (props.statusFilter === 'with_contract' && !employmentType.with_end_contract) return false
      if (props.statusFilter === 'without_contract' && employmentType.with_end_contract) return false
    }

    return true
  })
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.employment-type-table {
  margin-top: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

@media (max-width: 768px) {
  .employment-type-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
