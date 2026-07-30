<template>
  <div class="position-table">

    <el-table
      :data="filteredPositions"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No positions found"
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
        label="Position Name" 
        min-width="250"
        align="left"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.type" 
        prop="is_administrative_position" 
        label="Type" 
        width="140" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.is_administrative_position ? 'warning' : 'info'" size="small">
            {{ row.is_administrative_position ? 'Administrative' : 'Non-Administrative' }}
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
  positions: {
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
  typeFilter: {
    type: String,
    default: ''
  },
  visible: {
    type: Object,
    default: () => ({
      id: true,
      name: true,
      type: true,
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
const filteredPositions = computed(() => {
  let filtered = props.positions.filter(position => {
    // Search filter
    if (props.searchTerm) {
      const searchTerm = (props.searchTerm || '').trim().toLowerCase()
      if (searchTerm) {
        const positionName = (position.name || '').trim().toLowerCase()
        if (!positionName.includes(searchTerm)) return false
      }
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !position.active) return false
      if (props.statusFilter === 'inactive' && position.active) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'administrative' && !position.is_administrative_position) return false
      if (props.typeFilter === 'non-administrative' && position.is_administrative_position) return false
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.positions.filter(position => {
    // Search filter
    if (props.searchTerm) {
      const searchTerm = (props.searchTerm || '').trim().toLowerCase()
      if (searchTerm) {
        const positionName = (position.name || '').trim().toLowerCase()
        if (!positionName.includes(searchTerm)) return false
      }
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !position.active) return false
      if (props.statusFilter === 'inactive' && position.active) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'administrative' && !position.is_administrative_position) return false
      if (props.typeFilter === 'non-administrative' && position.is_administrative_position) return false
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
  return props.positions.filter(position => {
    // Search filter
    if (props.searchTerm) {
      const searchTerm = (props.searchTerm || '').trim().toLowerCase()
      if (searchTerm) {
        const positionName = (position.name || '').trim().toLowerCase()
        if (!positionName.includes(searchTerm)) return false
      }
    }

    // Status filter
    if (props.statusFilter) {
      if (props.statusFilter === 'active' && !position.active) return false
      if (props.statusFilter === 'inactive' && position.active) return false
    }

    // Type filter
    if (props.typeFilter) {
      if (props.typeFilter === 'administrative' && !position.is_administrative_position) return false
      if (props.typeFilter === 'non-administrative' && position.is_administrative_position) return false
    }

    return true
  })
}

defineExpose({ getFilteredData })
</script>

<style scoped>
.position-table {
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
  .position-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
