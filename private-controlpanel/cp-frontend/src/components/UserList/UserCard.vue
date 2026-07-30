<template>
  <el-card class="user-card" shadow="hover">
    <div class="user-header">
      <el-avatar 
        :src="user.photo" 
        :size="60"
        class="user-avatar"
      >
        {{ getInitials(user.name) }}
      </el-avatar>
      <div class="user-info">
        <h3 class="user-name">{{ user.name }}</h3>
        <p class="user-email">{{ user.email }}</p>
        <p class="user-emp-no">Employee #{{ user.employee_no }}</p>
      </div>
      <div class="user-actions">
        <el-dropdown trigger="click">
          <el-button type="text" icon="MoreFilled" />
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="editUser">
                <el-icon><Edit /></el-icon>
                Edit User
              </el-dropdown-item>
              <el-dropdown-item @click="toggleLock" :class="{ 'danger': !user.locked }">
                <el-icon><Lock /></el-icon>
                {{ user.locked ? 'Unlock' : 'Lock' }} Account
              </el-dropdown-item>
              <el-dropdown-item @click="resetPassword">
                <el-icon><Key /></el-icon>
                Reset Password
              </el-dropdown-item>
              <el-dropdown-item @click="deleteUser" class="danger">
                <el-icon><Delete /></el-icon>
                Delete User
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>

    <div class="user-details">
      <div class="detail-row">
        <span class="label">Role:</span>
        <el-tag :type="user.is_admin ? 'danger' : 'primary'" size="small">
          {{ user.is_admin ? 'Admin' : 'User' }}
        </el-tag>
      </div>
      
      <div class="detail-row">
        <span class="label">Status:</span>
        <el-tag :type="getStatusType(user)" size="small">
          {{ getStatusText(user) }}
        </el-tag>
      </div>
      
      <div class="detail-row">
        <span class="label">Verified:</span>
        <el-tag :type="user.email_verified_at ? 'success' : 'warning'" size="small">
          {{ user.email_verified_at ? 'Yes' : 'No' }}
        </el-tag>
      </div>
      
      <div class="detail-row">
        <span class="label">Password Changed:</span>
        <el-tag :type="user.has_change_password ? 'success' : 'warning'" size="small">
          {{ user.has_change_password ? 'Yes' : 'No' }}
        </el-tag>
      </div>
    </div>

    <div class="user-footer">
      <div class="created-date">
        Created: {{ formatDate(user.created_at) }}
      </div>
      <div class="access-badges">
        <el-tag 
          v-if="user.with_hrm_access" 
          type="success" 
          size="small"
        >
          HRM
        </el-tag>
        <el-tag 
          v-if="user.with_hrt_access" 
          type="info" 
          size="small"
        >
          HRT
        </el-tag>
        <el-tag 
          v-if="user.with_hrp_access" 
          type="warning" 
          size="small"
        >
          HRP
        </el-tag>
        <el-tag 
          v-if="user.with_cpm_access" 
          type="danger" 
          size="small"
        >
          CPM
        </el-tag>
      </div>
    </div>
  </el-card>
</template>

<script setup>
import { Edit, Lock, Key, Delete, MoreFilled } from '@element-plus/icons-vue'

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['edit', 'delete', 'toggle-lock', 'reset-password'])

function getInitials(name) {
  if (!name) return 'U'
  return name.split('.').map(part => part.charAt(0)).join('').toUpperCase()
}

function getStatusType(user) {
  if (user.locked) return 'danger'
  if (user.with_expiration && user.expiration_date <= new Date()) return 'warning'
  return 'success'
}

function getStatusText(user) {
  if (user.locked) return 'Locked'
  if (user.with_expiration && user.expiration_date <= new Date()) return 'Expired'
  return 'Active'
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

function editUser() {
  emit('edit', props.user)
}

function deleteUser() {
  emit('delete', props.user)
}

function toggleLock() {
  emit('toggle-lock', props.user)
}

function resetPassword() {
  emit('reset-password', props.user)
}
</script>

<style scoped>
.user-card {
  height: 100%;
  transition: all 0.3s ease;
}

.user-card:hover {
  transform: translateY(-2px);
}

.user-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.user-avatar {
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
}

.user-email {
  margin: 0 0 2px 0;
  font-size: 14px;
  color: #6b7280;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-emp-no {
  margin: 0;
  font-size: 12px;
  color: #9ca3af;
}

.user-actions {
  flex-shrink: 0;
}

.user-details {
  margin-bottom: 16px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.label {
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.user-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}

.created-date {
  font-size: 12px;
  color: #9ca3af;
}

.access-badges {
  display: flex;
  gap: 4px;
}

.danger {
  color: #ef4444;
}
</style>
