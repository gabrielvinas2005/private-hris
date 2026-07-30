<template>
  <div class="user-list-table">
    <el-table 
      :data="users" 
      border 
      stripe 
      v-loading="loading"
      @selection-change="handleSelectionChange"
      style="width: 100%"
    >
      <el-table-column type="selection" width="55" />
      <el-table-column prop="name" label="Name" sortable min-width="200">
        <template #default="{ row }">
          <div class="user-info">
            <el-avatar 
              :src="row.photo" 
              :size="40"
              class="user-avatar"
            >
              {{ getInitials(row.name) }}
            </el-avatar>
            <div class="user-details">
              <div class="user-name">{{ row.name }}</div>
              <div class="user-email">{{ row.email }}</div>
            </div>
          </div>
        </template>
      </el-table-column>
      <el-table-column prop="employee_no" label="Employee No." sortable width="120" />
      <el-table-column prop="is_admin" label="Role" width="100">
        <template #default="{ row }">
          <el-tag :type="row.is_admin ? 'danger' : 'primary'">
            {{ row.is_admin ? 'Admin' : 'User' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="locked" label="Status" width="100">
        <template #default="{ row }">
          <el-tag :type="getStatusType(row)">
            {{ getStatusText(row) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="email_verified_at" label="Verified" width="120">
        <template #default="{ row }">
          <el-tag :type="row.email_verified_at ? 'success' : 'warning'">
            {{ row.email_verified_at ? 'Verified' : 'Pending' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Access Rights" width="150" fixed="right">
        <template #default="{ row }">
          <el-button 
            size="small" 
            type="warning"
            @click="accessRights(row)"
            icon="Key"
          >
            Access Rights
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    
    <!-- Pagination -->
    <div class="pagination-container">
      <el-pagination
        v-model:current-page="currentPage"
        v-model:page-size="pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="totalUsers"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
        background
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
  users: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  totalUsers: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['selection-change', 'access-rights', 'page-change', 'size-change'])

const selectedUsers = ref([])
const currentPage = ref(1)
const pageSize = ref(10)

function handleSelectionChange(selection) {
  selectedUsers.value = selection
  emit('selection-change', selection)
}

function accessRights(user) {
  emit('access-rights', user)
}

function handleSizeChange(newSize) {
  pageSize.value = newSize
  currentPage.value = 1 // Reset to first page when changing size
  emit('size-change', { page: currentPage.value, size: newSize })
}

function handleCurrentChange(newPage) {
  currentPage.value = newPage
  emit('page-change', { page: newPage, size: pageSize.value })
}

function getInitials(name) {
  if (!name) return 'U'
  return name.split('.').map(part => part.charAt(0)).join('').toUpperCase()
}

function getStatusType(user) {
  const isLocked = !!user.locked
  const hasExpiration = !!user.with_expiration
  const expDate = user.expiration_date instanceof Date
    ? user.expiration_date
    : (user.expiration_date ? new Date(user.expiration_date) : null)
  const isExpired = hasExpiration && expDate && expDate <= new Date()
  if (isLocked) return 'danger'
  if (isExpired) return 'warning'
  return 'success'
}

function getStatusText(user) {
  const isLocked = !!user.locked
  const hasExpiration = !!user.with_expiration
  const expDate = user.expiration_date instanceof Date
    ? user.expiration_date
    : (user.expiration_date ? new Date(user.expiration_date) : null)
  const isExpired = hasExpiration && expDate && expDate <= new Date()
  if (isLocked) return 'Locked'
  if (isExpired) return 'Expired'
  return 'Active'
}

</script>

<style scoped>
.user-list-table {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  flex-shrink: 0;
}

.user-details {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 2px;
}

.user-email {
  font-size: 12px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pagination-container {
  display: flex;
  justify-content: center;
  padding: 20px 0;
  background: white;
  border-radius: 0 0 12px 12px;
  border-top: 1px solid #e6e6e6;
}
</style>
