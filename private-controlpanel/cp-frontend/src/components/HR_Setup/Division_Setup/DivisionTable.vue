<template>
  <div class="division-table">

    <el-table
      :data="filteredDivisions"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No divisions found"
      ref="tableRef"
    >
      <el-table-column 
        v-if="visible.code" 
        prop="code" 
        label="Code" 
        width="100"
        align="center"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.name" 
        prop="name" 
        label="Division Name" 
        min-width="200"
        align="left"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.office" 
        prop="office" 
        label="Department" 
        width="150" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <span v-if="row.office">{{ row.office }}</span>
          <el-tag v-else type="info" size="small">No Department</el-tag>
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.division_chief" 
        prop="division_chief" 
        label="Division Chief" 
        width="150" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <span v-if="row.division_chief">{{ row.division_chief }}</span>
          <el-tag v-else type="info" size="small">No Chief</el-tag>
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
        width="100" 
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
  divisions: {
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
  typeFilter: {
    type: String,
    default: ''
  },
  visible: {
    type: Object,
    default: () => ({
      code: true,
      name: true,
      office: true,
      division_chief: true,
      status: true,
      actions: true
    })
  }
})

// Emits
const emit = defineEmits(['edit', 'page-change'])

// Refs
const tableRef = ref(null)

// State
const currentPage = ref(1)
const pageSize = ref(10)

// Computed
const filteredDivisions = computed(() => {
  let filtered = props.divisions.filter(division => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        division.name.toLowerCase().includes(searchLower) ||
        division.code?.toLowerCase().includes(searchLower) ||
        division.division_chief?.toLowerCase().includes(searchLower) ||
        division.office?.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'active' && !division.active) return false
      if (props.typeFilter === 'inactive' && division.active) return false
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.divisions.filter(division => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        division.name.toLowerCase().includes(searchLower) ||
        division.code?.toLowerCase().includes(searchLower) ||
        division.division_chief?.toLowerCase().includes(searchLower) ||
        division.office?.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'active' && !division.active) return false
      if (props.typeFilter === 'inactive' && division.active) return false
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
  return props.divisions.filter(division => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        division.name.toLowerCase().includes(searchLower) ||
        division.code?.toLowerCase().includes(searchLower) ||
        division.division_chief?.toLowerCase().includes(searchLower) ||
        division.office?.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'active' && !division.active) return false
      if (props.typeFilter === 'inactive' && division.active) return false
    }

    return true
  })
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.division-table {
  margin-top: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

@media (max-width: 768px) {
  .division-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
