<template>
  <div class="office-table">

    <el-table
      :data="filteredOffices"
      v-loading="loading"
      stripe
      border
      style="width: 100%"
      empty-text="No offices found"
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
        label="Office Name" 
        min-width="200"
        align="left"
        header-align="center"
      />
      
      <el-table-column 
        v-if="visible.supervisor" 
        prop="supervisor" 
        label="Supervisor" 
        width="120" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <span v-if="row.supervisor">{{ row.supervisor }}</span>
          <el-tag v-else type="info" size="small">No Supervisor</el-tag>
        </template>
      </el-table-column>
      
      <el-table-column 
        v-if="visible.type" 
        prop="is_academic" 
        label="Type" 
        width="120" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-tag :type="row.is_academic ? 'success' : 'primary'" size="small">
            {{ row.is_academic ? 'Academic' : 'Non-Academic' }}
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
        width="160" 
        align="center"
        header-align="center"
      >
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="handleEdit(row)"
            :loading="loading"
          >
            Edit
          </el-button>
          <el-button
            type="danger"
            size="small"
            @click="handleDelete(row)"
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
  offices: {
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
      supervisor: true,
      type: true,
      status: true,
      actions: true
    })
  }
})

// Emits
const emit = defineEmits(['edit', 'delete', 'page-change', 'export-data'])

const handleEdit = (row) => {
  emit('edit', row)
}

const handleDelete = (row) => {
  emit('delete', row)
}

// Refs
const tableRef = ref(null)

// State
const currentPage = ref(1)
const pageSize = ref(10)

// Computed
const filteredOffices = computed(() => {
  let filtered = props.offices.filter(office => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        office.name.toLowerCase().includes(searchLower) ||
        office.code?.toLowerCase().includes(searchLower) ||
        office.supervisor?.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Type filter - handle combined filters (e.g., "academic|active")
    if (props.typeFilter) {
      const filters = props.typeFilter.includes('|') 
        ? props.typeFilter.split('|') 
        : [props.typeFilter]
      
      for (const filter of filters) {
        if (filter === 'academic' && !office.is_academic) return false
        if (filter === 'non-academic' && office.is_academic) return false
        if (filter === 'active' && !office.active) return false
        if (filter === 'inactive' && office.active) return false
      }
    }

    return true
  })

  // Pagination
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filtered.slice(start, end)
})

const totalItems = computed(() => {
  let filtered = props.offices.filter(office => {
    // Search filter
    if (props.searchTerm) {
      const searchLower = props.searchTerm.toLowerCase()
      const matchesSearch = 
        office.name.toLowerCase().includes(searchLower) ||
        office.code?.toLowerCase().includes(searchLower) ||
        office.supervisor?.toLowerCase().includes(searchLower)
      
      if (!matchesSearch) return false
    }

    // Type filter - handle combined filters (e.g., "academic|active")
    if (props.typeFilter) {
      const filters = props.typeFilter.includes('|') 
        ? props.typeFilter.split('|') 
        : [props.typeFilter]
      
      for (const filter of filters) {
        if (filter === 'academic' && !office.is_academic) return false
        if (filter === 'non-academic' && office.is_academic) return false
        if (filter === 'active' && !office.active) return false
        if (filter === 'inactive' && office.active) return false
      }
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

// Expose filtered data for export
defineExpose({
  getFilteredData: () => {
    let filtered = props.offices.filter(office => {
      // Search filter
      if (props.searchTerm) {
        const searchLower = props.searchTerm.toLowerCase()
        const matchesSearch = 
          office.name.toLowerCase().includes(searchLower) ||
          office.code?.toLowerCase().includes(searchLower) ||
          office.supervisor?.toLowerCase().includes(searchLower)
        
        if (!matchesSearch) return false
      }

      // Type filter - handle combined filters (e.g., "academic|active")
      if (props.typeFilter) {
        const filters = props.typeFilter.includes('|') 
          ? props.typeFilter.split('|') 
          : [props.typeFilter]
        
        for (const filter of filters) {
          if (filter === 'academic' && !office.is_academic) return false
          if (filter === 'non-academic' && office.is_academic) return false
          if (filter === 'active' && !office.active) return false
          if (filter === 'inactive' && office.active) return false
        }
      }

      return true
    })
    return filtered
  }
})
</script>

<style scoped>
.office-table {
  margin-top: 20px;
}

.pagination-container {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}

@media (max-width: 768px) {
  .office-table {
    overflow-x: auto;
  }
  
  .pagination-container {
    margin-top: 15px;
  }
}
</style>
