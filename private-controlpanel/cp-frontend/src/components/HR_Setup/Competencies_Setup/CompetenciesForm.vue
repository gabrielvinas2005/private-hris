<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="rules"
    label-width="120px"
    label-position="left"
  >
    <!-- Name Field -->
    <el-form-item label="Name" prop="name">
      <el-input
        v-model="formData.name"
        placeholder="Enter competency name"
        clearable
        :disabled="loading"
      />
    </el-form-item>

    <!-- Active Status -->
    <el-form-item label="Active" prop="active">
      <el-switch
        v-model="formData.active"
        :disabled="loading"
        active-text="Active"
        inactive-text="Inactive"
      />
    </el-form-item>

    <!-- Subcompetencies Section -->
    <el-form-item label="Subcompetencies">
      <div class="subcompetencies-section">
        <div class="subcompetencies-header">
          <el-button
            type="primary"
            size="small"
            @click="addSubcompetency"
            :disabled="loading"
            :icon="Plus"
          >
            Add Subcompetency
          </el-button>
        </div>

        <div v-if="formData.subcompetencies.length === 0" class="no-subcompetencies">
          <el-empty description="No subcompetencies added" :image-size="80" />
        </div>

        <div v-else class="subcompetencies-list">
          <div
            v-for="(sub, index) in formData.subcompetencies"
            :key="index"
            class="subcompetency-item"
          >
            <el-card shadow="hover">
              <div class="subcompetency-header">
                <span class="subcompetency-number">#{{ index + 1 }}</span>
                <el-button
                  type="danger"
                  size="small"
                  @click="removeSubcompetency(index)"
                  :disabled="loading"
                  :icon="Delete"
                  circle
                />
              </div>

              <el-row :gutter="16">
                <el-col :span="8">
                  <el-form-item
                    :label="`Code ${index + 1}`"
                    :prop="`subcompetencies.${index}.code`"
                  >
                    <el-input
                      v-model="sub.code"
                      placeholder="Enter code"
                      :disabled="loading"
                    />
                  </el-form-item>
                </el-col>
                <el-col :span="16">
                  <el-form-item
                    :label="`Name ${index + 1}`"
                    :prop="`subcompetencies.${index}.name`"
                  >
                    <el-input
                      v-model="sub.name"
                      placeholder="Enter subcompetency name"
                      :disabled="loading"
                    />
                  </el-form-item>
                </el-col>
              </el-row>

              <el-form-item
                :label="`Description ${index + 1}`"
                :prop="`subcompetencies.${index}.description`"
              >
                <el-input
                  v-model="sub.description"
                  type="textarea"
                  :rows="2"
                  placeholder="Enter description"
                  :disabled="loading"
                />
              </el-form-item>
            </el-card>
          </div>
        </div>
      </div>
    </el-form-item>

    <!-- Form Actions -->
    <el-form-item>
      <div class="form-actions">
        <el-button
          type="primary"
          @click="handleSubmit"
          :loading="loading"
          :icon="Check"
        >
          {{ isEdit ? 'Update' : 'Save' }}
        </el-button>
        <el-button
          @click="handleCancel"
          :disabled="loading"
          :icon="Close"
        >
          Cancel
        </el-button>
      </div>
    </el-form-item>
  </el-form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Check, Close, Plus, Delete } from '@element-plus/icons-vue'

const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      name: '',
      active: true,
      subcompetencies: []
    })
  },
  loading: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel', 'add-subcompetency', 'remove-subcompetency'])

const formRef = ref(null)

// Form validation rules
const rules = reactive({
  name: [
    { required: true, message: 'Please enter competency name', trigger: 'blur' },
    { min: 3, max: 100, message: 'Name must be between 3 and 100 characters', trigger: 'blur' }
  ]
})

function addSubcompetency() {
  emit('add-subcompetency')
}

function removeSubcompetency(index) {
  emit('remove-subcompetency', index)
}

function handleSubmit() {
  if (!formRef.value) return
  
  formRef.value.validate((valid) => {
    if (valid) {
      emit('submit', { ...props.formData })
    }
  })
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  width: 100%;
}

.subcompetencies-section {
  width: 100%;
}

.subcompetencies-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #ebeef5;
}

.subcompetencies-header h4 {
  margin: 0;
  color: #606266;
  font-weight: 600;
  font-size: 16px;
}

.subcompetencies-header .el-button {
  margin-left: auto;
}

.no-subcompetencies {
  text-align: center;
  padding: 20px;
  border: 2px dashed #dcdfe6;
  border-radius: 8px;
  background-color: #fafafa;
}

.subcompetencies-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.subcompetency-item {
  width: 100%;
}

.subcompetency-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.subcompetency-number {
  font-weight: 600;
  color: #409eff;
  font-size: 14px;
}

:deep(.el-form-item__label) {
  font-weight: 500;
  color: #606266;
}

:deep(.el-input__wrapper) {
  border-radius: 6px;
}

:deep(.el-switch) {
  --el-switch-on-color: #67c23a;
  --el-switch-off-color: #dcdfe6;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
  min-width: auto;
}

:deep(.el-button .el-icon) {
  margin-right: 4px;
}

:deep(.el-button--primary) {
  background-color: #409eff;
  border-color: #409eff;
}

:deep(.el-button--primary:hover) {
  background-color: #66b1ff;
  border-color: #66b1ff;
}

:deep(.el-card) {
  border-radius: 8px;
}

:deep(.el-card__body) {
  padding: 16px;
}
</style>
