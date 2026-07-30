<template>
  <MainLayout>
    <template #header>
      <div class="header">
        <div class="title">User List</div>
        <div class="header-actions">
          <input v-model="query" class="search" placeholder="Search users..." />
          <el-button type="primary" @click="showAddUserForm" icon="Plus">
            Add Users
          </el-button>
        </div>
      </div>
    </template>

    <section class="toolbar">
      <div class="toolbar-left">
        <el-select v-model="statusFilter" placeholder="Filter by status" clearable style="width: 180px">
          <el-option label="Active" value="active" />
          <el-option label="Locked" value="locked" />
          <el-option label="Expired" value="expired" />
        </el-select>
        <el-select v-model="roleFilter" placeholder="Filter by role" clearable style="width: 150px">
          <el-option label="Admin" value="admin" />
          <el-option label="User" value="user" />
        </el-select>
        <el-button @click="toggleViewMode" :icon="viewMode === 'table' ? 'Grid' : 'List'">
          {{ viewMode === 'table' ? 'Card View' : 'Table View' }}
        </el-button>
      </div>
      <div class="toolbar-right">
        <el-button @click="refreshData" :loading="loading" icon="Refresh">
          Refresh
        </el-button>
      </div>
    </section>

    <!-- Table View -->
    <UserListTable 
      v-if="viewMode === 'table'"
      :users="paginatedUsers"
      :loading="loading"
      :total-users="filteredUsers.length"
      @selection-change="handleSelectionChange"
      @access-rights="handleAccessRights"
      @page-change="handlePageChange"
      @size-change="handleSizeChange"
    />

    <!-- Card View -->
    <div v-else class="card-view">
      <div class="user-cards-grid">
        <UserCard
          v-for="user in filteredUsers"
          :key="user.id"
          :user="user"
          @edit="editUser"
          @delete="deleteUser"
          @toggle-lock="toggleUserLock"
          @reset-password="resetUserPassword"
        />
      </div>
    </div>

    <!-- Add User Form -->
    <UserForm
      ref="userFormRef"
      v-model="showForm"
      :available-employees="availableEmployees"
      :loading-employees="loadingEmployees"
      @submit="handleAddUsers"
      @close="closeForm"
    />

    <!-- Access Rights Modal -->
    <AccessRightsModal 
      v-model="showAccessRights"
      :user-id="selectedUserId"
      @saved="handleAccessRightsSaved"
    />
  </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import MainLayout from '../../Layout/MainLayout.vue'
import UserListTable from '../../components/UserList/UserListTable.vue'
import UserCard from '../../components/UserList/UserCard.vue'
import UserForm from '../../components/UserList/UserForm.vue'
import AccessRightsModal from '../../components/UserList/AccessRightsModal.vue'
import { useUsers } from '../../composables/useUsers.js'

// Use the users composable
const {
  users,
  availableEmployees,
  loading,
  loadingEmployees,
  fetchUsers,
  fetchAvailableEmployees,
  addUsers,
  filterUsers
} = useUsers()

// Local state
const showForm = ref(false)
const showAccessRights = ref(false)
const selectedUserId = ref(null)
const viewMode = ref('table') // 'table' or 'card'
const userFormRef = ref(null)

// Pagination state
const currentPage = ref(1)
const pageSize = ref(10)

// Filters
const query = ref('')
const roleFilter = ref('')
const statusFilter = ref('')

// Computed properties
const filteredUsers = computed(() => {
  return filterUsers({
    role: roleFilter.value,
    status: statusFilter.value,
    search: query.value
  })
})

const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value
  const end = start + pageSize.value
  return filteredUsers.value.slice(start, end)
})

// Methods
function showAddUserForm() {
  fetchAvailableEmployees()
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

async function handleAddUsers(formData) {
  try {
    const result = await addUsers(formData)
    // Resolve the promise in the form component
    if (userFormRef.value && userFormRef.value.resolveSubmit) {
      userFormRef.value.resolveSubmit(result)
    }
    if (result.success) {
      showForm.value = false
    }
  } catch (error) {
    console.error('Error in handleAddUsers:', error)
    // Reject the promise in the form component
    if (userFormRef.value && userFormRef.value.rejectSubmit) {
      userFormRef.value.rejectSubmit(error)
    }
  }
}

function handleSelectionChange(selection) {
  console.log('Selected users:', selection)
}

function handleAccessRights(user) {
  selectedUserId.value = user.id
  showAccessRights.value = true
}

function handlePageChange({ page, size }) {
  currentPage.value = page
  pageSize.value = size
}

function handleSizeChange({ page, size }) {
  currentPage.value = page
  pageSize.value = size
}

function handleAccessRightsSaved() {
  // Refresh user data after access rights are updated
  fetchUsers()
  showAccessRights.value = false
  selectedUserId.value = null
}

function toggleUserLock(user) {
  ElMessage.info('Lock/unlock functionality will be available when backend route is implemented')
  console.log('Toggle lock for user:', user)
}

function resetUserPassword(user) {
  ElMessage.info('Password reset functionality will be available when backend route is implemented')
  console.log('Reset password for user:', user)
}

function toggleViewMode() {
  viewMode.value = viewMode.value === 'table' ? 'card' : 'table'
}

function refreshData() {
  fetchUsers()
}

// Lifecycle
// Watch for filter changes and reset pagination
watch([query, roleFilter, statusFilter], () => {
  currentPage.value = 1
})

onMounted(() => {
  fetchUsers()
})
</script>

<style scoped>
.header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  gap: 12px; 
}

.title { 
  font-weight: 700; 
  font-size: 18px; 
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.search { 
  padding: 8px 10px; 
  border: 1px solid #e5e7eb; 
  border-radius: 8px; 
  min-width: 220px; 
}

.toolbar { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin: 12px 0 16px; 
  gap: 16px;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-view {
  margin-top: 16px;
}

.user-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
  padding: 20px 0;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 8px;
  }
  
  .toolbar {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .toolbar-left,
  .toolbar-right {
    justify-content: center;
  }
  
  .user-cards-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>



