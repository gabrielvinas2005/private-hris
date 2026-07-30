<template>
  <div class="company-info">
    <el-card class="info-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <div class="header-left">
            <el-icon class="header-icon"><OfficeBuilding /></el-icon>
            <span class="header-title">Company Information</span>
          </div>
          <div class="header-actions">
            <el-button
              type="primary"
              size="small"
              :icon="Edit"
              @click="$emit('edit')"
            >
              Edit
            </el-button>
          </div>
        </div>
      </template>

      <div v-if="loading" class="loading-container">
        <el-skeleton :rows="6" animated />
      </div>

      <div v-else-if="!company" class="no-data">
        <el-empty description="No company information available">
          <el-button type="primary" @click="$emit('add')">
            Add Company Information
          </el-button>
        </el-empty>
      </div>

      <div v-else class="company-details">
        <el-row :gutter="24">
          <!-- Company Logo -->
          <el-col :span="6">
            <div class="logo-section">
              <div class="logo-container">
                <img
                  v-if="company.logo"
                  :src="getLogoUrl(company)"
                  alt="Company Logo"
                  class="company-logo"
                />
                <div v-else class="no-logo">
                  <el-icon><Picture /></el-icon>
                  <span>No Logo</span>
                </div>
              </div>
            </div>
          </el-col>

          <!-- Company Details -->
          <el-col :span="18">
            <div class="details-section">
              <div class="detail-item">
                <div class="detail-label">
                  <el-icon><OfficeBuilding /></el-icon>
                  Company Name
                </div>
                <div class="detail-value">{{ company.name || 'Not specified' }}</div>
              </div>

              <div class="detail-item">
                <div class="detail-label">
                  <el-icon><Location /></el-icon>
                  Address
                </div>
                <div class="detail-value">{{ company.address || 'Not specified' }}</div>
              </div>

              <div class="detail-item">
                <div class="detail-label">
                  <el-icon><Message /></el-icon>
                  Email Address
                </div>
                <div class="detail-value">
                  <a v-if="company.email" :href="`mailto:${company.email}`" class="email-link">
                    {{ company.email }}
                  </a>
                  <span v-else>Not specified</span>
                </div>
              </div>

              <div class="detail-item">
                <div class="detail-label">
                  <el-icon><Phone /></el-icon>
                  Telephone Number
                </div>
                <div class="detail-value">
                  <a v-if="company.telephone_no" :href="`tel:${company.telephone_no}`" class="phone-link">
                    {{ company.telephone_no }}
                  </a>
                  <span v-else>Not specified</span>
                </div>
              </div>

              <div class="detail-item">
                <div class="detail-label">
                  <el-icon><Iphone /></el-icon>
                  Mobile Number
                </div>
                <div class="detail-value">
                  <a v-if="company.mobile_no" :href="`tel:${company.mobile_no}`" class="phone-link">
                    {{ company.mobile_no }}
                  </a>
                  <span v-else>Not specified</span>
                </div>
              </div>
            </div>
          </el-col>
        </el-row>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { 
  OfficeBuilding, 
  Picture, 
  Location, 
  Message, 
  Phone, 
  Iphone,
  Edit 
} from '@element-plus/icons-vue'
import { useCompany } from '../../../composables/useCompany.js'

// Props
const props = defineProps({
  company: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['edit', 'add'])

// Composables
const { getLogoUrl } = useCompany()
</script>

<style scoped>
.company-info {
  width: 100%;
}

.info-card {
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-icon {
  font-size: 20px;
  color: #409eff;
}

.header-title {
  font-size: 18px;
  font-weight: 600;
  color: #303133;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.loading-container {
  padding: 20px 0;
}

.no-data {
  padding: 40px 0;
}

.company-details {
  padding: 8px 0;
}

.logo-section {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 16px 0;
}

.logo-container {
  width: 120px;
  height: 120px;
  border: 2px solid #e4e7ed;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background-color: #fafafa;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.company-logo {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.no-logo {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  color: #c0c4cc;
  font-size: 12px;
}

.no-logo .el-icon {
  font-size: 32px;
}

.details-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #606266;
}

.detail-label .el-icon {
  font-size: 16px;
  color: #909399;
}

.detail-value {
  font-size: 16px;
  color: #303133;
  font-weight: 500;
  padding-left: 24px;
  word-break: break-word;
}

.email-link,
.phone-link {
  color: #409eff;
  text-decoration: none;
  transition: color 0.3s;
}

.email-link:hover,
.phone-link:hover {
  color: #66b1ff;
  text-decoration: underline;
}

:deep(.el-card__header) {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid #e4e7ed;
  padding: 20px 24px;
}

:deep(.el-card__body) {
  padding: 24px;
}

:deep(.el-empty) {
  padding: 40px 0;
}

:deep(.el-empty__description) {
  color: #909399;
  margin-top: 16px;
}
</style>
