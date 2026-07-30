<template>
  <div class="company-form">
    <el-form
      ref="formRef"
      :model="formData"
      :rules="formRules"
      label-width="140px"
      label-position="left"
      @submit.prevent="handleSubmit"
    >
      <el-row :gutter="24">
        <!-- Company Information -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon><OfficeBuilding /></el-icon>
                <span>Company Information</span>
              </div>
            </template>

            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item label="Company Name" prop="name" required>
                  <el-input
                    v-model="formData.name"
                    placeholder="Enter company name"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Email Address" prop="email" required>
                  <el-input
                    v-model="formData.email"
                    type="email"
                    placeholder="Enter company email"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item label="Telephone No." prop="telephone_no">
                  <el-input
                    v-model="formData.telephone_no"
                    placeholder="Enter telephone number"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Mobile No." prop="mobile_no">
                  <el-input
                    v-model="formData.mobile_no"
                    placeholder="Enter mobile number"
                    clearable
                    :disabled="saving"
                  />
                </el-form-item>
              </el-col>
            </el-row>

            <el-form-item label="Address" prop="address" required>
              <el-input
                v-model="formData.address"
                type="textarea"
                :rows="3"
                placeholder="Enter company address"
                :disabled="saving"
              />
            </el-form-item>
          </el-card>
        </el-col>

        <!-- Company Logo -->
        <el-col :span="24">
          <el-card class="form-section" shadow="never">
            <template #header>
              <div class="section-header">
                <el-icon><Picture /></el-icon>
                <span>Company Logo</span>
              </div>
            </template>

            <el-row :gutter="16">
              <el-col :span="12">
                <el-form-item label="Upload Logo" prop="logo">
                  <el-upload
                    ref="uploadRef"
                    :auto-upload="false"
                    :show-file-list="false"
                    :on-change="handleLogoChange"
                    :before-upload="beforeLogoUpload"
                    accept="image/*"
                    :disabled="saving"
                  >
                    <el-button type="primary" :loading="saving">
                      <el-icon><Upload /></el-icon>
                      Choose Logo
                    </el-button>
                    <template #tip>
                      <div class="el-upload__tip">
                        JPG, PNG or GIF file. Max size 2MB.
                      </div>
                    </template>
                  </el-upload>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <div class="logo-preview">
                  <div class="preview-label">Logo Preview:</div>
                  <div class="preview-container">
                    <img
                      v-if="logoPreview"
                      :src="logoPreview"
                      alt="Company Logo Preview"
                      class="logo-image"
                    />
                    <div v-else class="no-logo">
                      <el-icon><Picture /></el-icon>
                      <span>No logo selected</span>
                    </div>
                  </div>
                </div>
              </el-col>
            </el-row>
          </el-card>
        </el-col>
      </el-row>

      <!-- Action Buttons -->
      <div class="form-actions">
        <el-button
          type="primary"
          size="large"
          :loading="saving"
          @click="handleSubmit"
        >
          <el-icon><Check /></el-icon>
          {{ saving ? 'Saving...' : 'Save Company Information' }}
        </el-button>
        <el-button
          size="large"
          :disabled="saving"
          @click="handleReset"
        >
          <el-icon><Refresh /></el-icon>
          Reset
        </el-button>
      </div>
    </el-form>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { 
  OfficeBuilding, 
  Picture, 
  Upload, 
  Check, 
  Refresh 
} from '@element-plus/icons-vue'
import { useCompany } from '../../../composables/useCompany.js'

// Props
const props = defineProps({
  companyData: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['saved', 'reset'])

// Composables
const { 
  saveCompany, 
  validateCompanyForm, 
  formatFileSize,
  getLogoUrl,
  saving 
} = useCompany()

// Refs
const formRef = ref()
const uploadRef = ref()

// Form data
const formData = ref({
  id: null,
  name: '',
  address: '',
  email: '',
  telephone_no: '',
  mobile_no: '',
  logo: null
})

// Logo preview
const logoPreview = ref('')

// Form validation rules
const formRules = {
  name: [
    { required: true, message: 'Company name is required', trigger: 'blur' }
  ],
  address: [
    { required: true, message: 'Company address is required', trigger: 'blur' }
  ],
  email: [
    { required: true, message: 'Email address is required', trigger: 'blur' },
    { type: 'email', message: 'Please enter a valid email address', trigger: 'blur' }
  ]
}

// Methods
function handleLogoChange(file) {
  if (file.raw) {
    formData.value.logo = file.raw
    createLogoPreview(file.raw)
  }
}

function beforeLogoUpload(file) {
  const isImage = file.type.startsWith('image/')
  const isLt2M = file.size / 1024 / 1024 < 2

  if (!isImage) {
    ElMessage.error('Logo must be an image file!')
    return false
  }
  if (!isLt2M) {
    ElMessage.error('Logo size must be less than 2MB!')
    return false
  }
  return false // Prevent auto upload
}

function createLogoPreview(file) {
  const reader = new FileReader()
  reader.onload = (e) => {
    logoPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

async function handleSubmit() {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    
    const validation = validateCompanyForm(formData.value)
    if (!validation.isValid) {
      // Show validation errors
      Object.keys(validation.errors).forEach(field => {
        ElMessage.error(validation.errors[field])
      })
      return
    }

    const result = await saveCompany(formData.value, formData.value.id)
    if (result.success) {
      emit('saved', result.data)
    }
  } catch (error) {
    console.error('Form validation failed:', error)
  }
}

function handleReset() {
  ElMessageBox.confirm(
    'Are you sure you want to reset the form? All unsaved changes will be lost.',
    'Reset Form',
    {
      confirmButtonText: 'Reset',
      cancelButtonText: 'Cancel',
      type: 'warning',
    }
  ).then(() => {
    formRef.value?.resetFields()
    // Reset to original company data if editing, otherwise clear
    if (props.companyData && props.companyData.id) {
      formData.value = {
        id: props.companyData.id || null,
        name: props.companyData.name || '',
        address: props.companyData.address || '',
        email: props.companyData.email || '',
        telephone_no: props.companyData.telephone_no || '',
        mobile_no: props.companyData.mobile_no || '',
        logo: null
      }
      if (props.companyData.logo) {
        logoPreview.value = getLogoUrl(props.companyData)
      } else {
        logoPreview.value = ''
      }
    } else {
      formData.value = {
        id: null,
        name: '',
        address: '',
        email: '',
        telephone_no: '',
        mobile_no: '',
        logo: null
      }
      logoPreview.value = ''
    }
    uploadRef.value?.clearFiles()
    emit('reset')
  }).catch(() => {
    // User cancelled
  })
}

// Watch for prop changes
watch(() => props.companyData, (newData) => {
  if (newData && Object.keys(newData).length > 0) {
    formData.value = {
      id: newData.id || null,
      name: newData.name || '',
      address: newData.address || '',
      email: newData.email || '',
      telephone_no: newData.telephone_no || '',
      mobile_no: newData.mobile_no || '',
      logo: null
    }
    
    // Set logo preview if company has logo
    if (newData.logo) {
      logoPreview.value = getLogoUrl(newData)
    }
  }
}, { immediate: true, deep: true })

// Initialize form with existing data
onMounted(() => {
  if (props.companyData && Object.keys(props.companyData).length > 0) {
    formData.value = {
      id: props.companyData.id || null,
      name: props.companyData.name || '',
      address: props.companyData.address || '',
      email: props.companyData.email || '',
      telephone_no: props.companyData.telephone_no || '',
      mobile_no: props.companyData.mobile_no || '',
      logo: null
    }
    
    if (props.companyData.logo) {
      logoPreview.value = getLogoUrl(props.companyData)
    }
  }
})
</script>

<style scoped>
.company-form {
  max-width: 100%;
}

.form-section {
  margin-bottom: 24px;
  border: 1px solid #e4e7ed;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 600;
  color: #303133;
}

.section-header .el-icon {
  font-size: 18px;
  color: #409eff;
}

.logo-preview {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.preview-label {
  font-weight: 500;
  color: #606266;
  font-size: 14px;
}

.preview-container {
  width: 120px;
  height: 120px;
  border: 2px dashed #dcdfe6;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background-color: #fafafa;
}

.logo-image {
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
  font-size: 24px;
}

.form-actions {
  display: flex;
  justify-content: center;
  gap: 16px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #e4e7ed;
}

.el-form-item {
  margin-bottom: 20px;
}

:deep(.el-upload__tip) {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}

:deep(.el-card__header) {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e4e7ed;
  padding: 16px 20px;
}

:deep(.el-card__body) {
  padding: 24px 20px;
}
</style>
