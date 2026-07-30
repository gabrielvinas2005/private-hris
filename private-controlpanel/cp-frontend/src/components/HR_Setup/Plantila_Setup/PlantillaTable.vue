<template>
  <div class="plantilla-table">

    <el-table
      :data="filteredPlantillas"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No plantillas found"
      ref="tableRef"
    >
      <el-table-column
        v-if="visible.serial"
        label="#"
        width="80"
        align="center"
        header-align="center"
      >
        <template #default="{ $index }">
          {{ (currentPage - 1) * pageSize + $index + 1 }}
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.code" 
        prop="code" 
        label="Code" 
        width="120" 
        align="center"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.position" 
        prop="position" 
        label="Position" 
        min-width="200"
        align="left"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.step" 
        prop="step" 
        label="Step" 
        width="100" 
        align="center"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.grade" 
        prop="grade" 
        label="Grade" 
        width="100" 
        align="center"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.department" 
        prop="department" 
        label="Department" 
        min-width="150"
        align="left"
        header-align="center"
      >
        <template #default="{ row }">
          <span v-if="row.department">{{ row.department }}</span>
          <span v-else class="text-muted">-</span>
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.status" 
        prop="status" 
        label="Status" 
        width="100" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.status === 'Vacant' ? 'success' : 'warning'" size="small">
            {{ row.status }}
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
  plantillas: {
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
  departmentFilter: {
    type: String,
    default: ''
  },
  visible: {
    type: Object,
    default: () => ({
      serial: true,
      code: true,
      position: true,
      step: true,
      grade: true,
      department: true,
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
const filteredPlantillas = computed(() => {
  let filtered = props.plantillas.filter(plantilla => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        plantilla.code.toLowerCase().includes(searchLower) ||
        plantilla.position.toLowerCase().includes(searchLower) ||
        (plantilla.department && plantilla.department.toLowerCase().includes(searchLower))
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !plantilla.active) return false
      if (props.statusFilter === 'inactive' && plantilla.active) return false
      if (props.statusFilter === 'vacant' && plantilla.status !== 'Vacant') return false
      if (props.statusFilter === 'occupied' && plantilla.status !== 'Occupied') return false
    }

    // Department filter
    if (props.departmentFilter) {
      if (plantilla.department !== props.departmentFilter) return false
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.plantillas.filter(plantilla => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        plantilla.code.toLowerCase().includes(searchLower) ||
        plantilla.position.toLowerCase().includes(searchLower) ||
        (plantilla.department && plantilla.department.toLowerCase().includes(searchLower))
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !plantilla.active) return false
      if (props.statusFilter === 'inactive' && plantilla.active) return false
      if (props.statusFilter === 'vacant' && plantilla.status !== 'Vacant') return false
      if (props.statusFilter === 'occupied' && plantilla.status !== 'Occupied') return false
    }

    // Department filter
    if (props.departmentFilter) {
      if (plantilla.department !== props.departmentFilter) return false
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
  return props.plantillas.filter(plantilla => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        plantilla.code.toLowerCase().includes(searchLower) ||
        plantilla.position.toLowerCase().includes(searchLower) ||
        (plantilla.department && plantilla.department.toLowerCase().includes(searchLower))
      
      if (!matchesSearch) return false
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !plantilla.active) return false
      if (props.statusFilter === 'inactive' && plantilla.active) return false
      if (props.statusFilter === 'vacant' && plantilla.status !== 'Vacant') return false
      if (props.statusFilter === 'occupied' && plantilla.status !== 'Occupied') return false
    }

    // Department filter
    if (props.departmentFilter) {
      if (plantilla.department !== props.departmentFilter) return false
    }

    return true
  })
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.plantilla-table {
  margin-top: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

.text-muted {
  color: #999;
}

@media (max-width: 768px) {
  .plantilla-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
