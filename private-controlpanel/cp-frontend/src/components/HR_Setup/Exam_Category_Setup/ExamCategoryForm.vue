<template>
  <el-form
    ref="formRef"
    :model="formData"
    :rules="rules"
    label-width="200px"
    label-position="top"
    class="exam-category-form"
  >
    <!-- Category Name Field -->
    <el-form-item label="Category Name" prop="name">
      <el-input
        v-model="formData.name"
        :disabled="loading"
        placeholder="Enter category name"
        style="width: 100%"
      />
    </el-form-item>

    <!-- Description Field -->
    <el-form-item label="Description" prop="description">
      <el-input
        v-model="formData.description"
        type="textarea"
        :rows="3"
        :disabled="loading"
        placeholder="Enter category description"
        style="width: 100%"
      />
    </el-form-item>

    <!-- Subcategories Section -->
    <el-form-item label="Subcategories">
      <div class="subcategories-section">
        <div class="subcategories-header">
          <el-button
            type="primary"
            size="small"
            @click="handleAddSubcategory"
            :icon="Plus"
          >
            Add Subcategory
          </el-button>
        </div>

        <div v-if="formData.subcategories.length === 0" class="no-subcategories">
          <el-empty description="No subcategories added yet" :image-size="80" />
        </div>

        <div v-else class="subcategories-list">
          <div
            v-for="(subcategory, index) in formData.subcategories"
            :key="subcategory._rowKey || subcategory.id || `idx-${index}`"
            class="subcategory-item"
          >
            <el-card shadow="hover" class="subcategory-card">
              <div class="subcategory-content">
                <div class="subcategory-fields">
                  <el-form-item
                    :label="`Subcategory ${index + 1} Name`"
                    :prop="`subcategories.${index}.sub_category`"
                    class="subcategory-field"
                  >
                    <el-input
                      v-model="subcategory.sub_category"
                      placeholder="Enter subcategory name"
                      :disabled="loading"
                    />
                  </el-form-item>

                  <el-form-item
                    label="Difficulty Level"
                    :prop="`subcategories.${index}.difficulty_level`"
                    class="subcategory-field"
                  >
                    <el-select
                      v-model="subcategory.difficulty_level"
                      placeholder="Select difficulty level"
                      :disabled="loading"
                      style="width: 100%"
                    >
                      <el-option
                        v-for="level in difficultyLevels"
                        :key="level.id"
                        :label="level.display_label || level.difficulty_level || level.name || `Level ${level.id}`"
                        :value="level.id"
                      />
                    </el-select>
                  </el-form-item>

                  <el-form-item
                    label="Existing Questions"
                    :prop="`subcategories.${index}.existing_questions`"
                    class="subcategory-field"
                  >
                    <el-input-number
                      v-model="subcategory.existing_questions"
                      :min="0"
                      :disabled="loading"
                      style="width: 100%"
                    />
                  </el-form-item>

                  <el-form-item
                    label="Essay Exam"
                    :prop="`subcategories.${index}.is_essay`"
                    class="subcategory-field"
                  >
                    <el-switch
                      v-model="subcategory.is_essay"
                      :disabled="loading"
                      active-text="Yes"
                      inactive-text="No"
                    />
                  </el-form-item>
                </div>

                <div class="subcategory-actions">
                  <el-button
                    type="danger"
                    size="small"
                    @click="handleRemoveSubcategory(index)"
                    :icon="Delete"
                    circle
                  />
                </div>
              </div>
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
import { ref, reactive, toRaw } from 'vue'
import { Plus, Delete, Check, Close } from '@element-plus/icons-vue'

const props = defineProps({
  formData: {
    type: Object,
    default: () => ({
      name: '',
      description: '',
      subcategories: []
    })
  },
  difficultyLevels: {
    type: Array,
    default: () => []
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

const emit = defineEmits(['submit', 'cancel', 'add-subcategory', 'remove-subcategory'])

const formRef = ref(null)

// Form validation rules
const rules = reactive({
  name: [
    { required: true, message: 'Category name is required', trigger: 'blur' }
  ],
  description: [
    { required: true, message: 'Description is required', trigger: 'blur' }
  ]
})

function handleAddSubcategory() {
  emit('add-subcategory')
}

function handleRemoveSubcategory(index) {
  emit('remove-subcategory', index)
}

function handleSubmit() {
  if (!formRef.value) return
  
  formRef.value.validate((valid) => {
    if (valid) {
      // Deep snapshot so the API gets the exact rows shown in the form (add/remove/edit subcategories).
      const raw = toRaw(props.formData)
      emit('submit', JSON.parse(JSON.stringify(raw)))
    }
  })
}

function handleCancel() {
  emit('cancel')
}
</script>

<style scoped>
.exam-category-form {
  padding: 20px;
}

.exam-category-form .el-form-item {
  margin-bottom: 24px;
}

.exam-category-form .el-form-item__label {
  font-weight: 600;
  color: #303133;
  font-size: 14px;
  margin-bottom: 8px;
}

.subcategories-section {
  width: 100%;
}

.subcategories-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding: 8px 0;
  border-bottom: 1px solid #ebeef5;
}

.subcategories-header h4 {
  margin: 0;
  color: #606266;
  font-weight: 600;
  font-size: 16px;
}

.subcategories-header .el-button {
  margin-left: auto;
}

.no-subcategories {
  text-align: center;
  padding: 40px 0;
}

.subcategories-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.subcategory-item {
  width: 100%;
}

.subcategory-card {
  border: none;           /* remove outer box border */
  border-radius: 0;
  box-shadow: none;
}

.subcategory-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.subcategory-fields {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr;
  gap: 16px;
}

.subcategory-field {
  margin-bottom: 0;
}

.subcategory-actions {
  display: flex;
  align-items: center;
  padding-top: 8px;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  width: 100%;
}

:deep(.el-input) {
  width: 100%;
}

:deep(.el-input .el-input__inner) {
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 14px;
  color: #606266;
}

:deep(.el-input .el-input__inner:focus) {
  border-color: #409eff;
  outline: none;
}

:deep(.el-textarea .el-textarea__inner) {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 14px;
  color: #606266;
}

:deep(.el-textarea .el-textarea__inner:focus) {
  border-color: #409eff;
  outline: none;
}

:deep(.el-select) {
  width: 100%;
}

:deep(.el-input-number) {
  width: 100%;
}

:deep(.el-button) {
  border-radius: 6px;
  font-weight: 500;
}

:deep(.el-button--primary) {
  background-color: #409eff;
  border-color: #409eff;
}

:deep(.el-button--primary:hover) {
  background-color: #66b1ff;
  border-color: #66b1ff;
}

:deep(.el-card__body) {
  padding: 16px;
}

@media (max-width: 768px) {
  .subcategory-fields {
    grid-template-columns: 1fr;
  }
  
  .subcategory-content {
    flex-direction: column;
  }
  
  .subcategory-actions {
    align-self: flex-end;
  }
}
</style>
