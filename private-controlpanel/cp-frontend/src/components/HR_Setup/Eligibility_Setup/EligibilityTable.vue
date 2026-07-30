<template>
  <div class="eligibility-table">

    <el-table
      :data="filteredEligibilities"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No eligibilities found"
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
        label="Eligibility Name" 
        min-width="300"
        align="left"
        header-align="center"
      />
      
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
  eligibilities: {
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
const filteredEligibilities = computed(() => {
  let filtered = props.eligibilities.filter(eligibility => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        eligibility.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !eligibility.active) return false
      if (props.statusFilter === 'inactive' && eligibility.active) return false
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.eligibilities.filter(eligibility => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        eligibility.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !eligibility.active) return false
      if (props.statusFilter === 'inactive' && eligibility.active) return false
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
  return props.eligibilities.filter(eligibility => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        eligibility.name.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !eligibility.active) return false
      if (props.statusFilter === 'inactive' && eligibility.active) return false
    }

    return true
  })
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.eligibility-table {
  margin-top: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

@media (max-width: 768px) {
  .eligibility-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
