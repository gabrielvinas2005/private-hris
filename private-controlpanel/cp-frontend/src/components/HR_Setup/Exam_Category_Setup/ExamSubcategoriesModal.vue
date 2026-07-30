<template>
  <el-dialog
    v-model="visible"
    :title="`Subcategories - ${category?.name || 'Exam Category'}`"
    width="800px"
    :before-close="handleClose"
    destroy-on-close
  >
    <div class="subcategories-modal">
      <div class="summary-card">
        <div class="summary-item">
          <span class="label">Category Code</span>
          <span class="value">{{ category?.category_code || 'N/A' }}</span>
        </div>
        <div class="summary-item">
          <span class="label">Total Subcategories</span>
          <span class="value">{{ subcategories.length }}</span>
        </div>
      </div>

      <el-empty
        v-if="!subcategories.length && !loading"
        description="No subcategories found for this category."
      />

      <el-table
        v-else
        :data="subcategories"
        border
        stripe
        height="320"
        v-loading="loading"
        class="subcategories-table"
      >
        <el-table-column prop="sub_category_code" label="Code" width="150" />
        <el-table-column prop="sub_category" label="Subcategory" min-width="200" show-overflow-tooltip />
        <el-table-column prop="difficulty_label" label="Difficulty" width="140">
          <template #default="{ row }">
            <el-tag size="small">{{ row.difficulty_label || `Level ${row.difficulty_level}` }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="existing_questions" label="Existing Questions" width="160" align="center" />
      </el-table>
    </div>

    <template #footer>
      <el-button @click="handleClose">Close</el-button>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  category: { type: Object, default: null },
  subcategories: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue', 'close'])
const visible = ref(false)

watch(
  () => props.modelValue,
  (val) => {
    visible.value = val
  },
  { immediate: true }
)

watch(visible, (val) => {
  emit('update:modelValue', val)
})

function handleClose() {
  visible.value = false
  emit('close')
}
</script>

<style scoped>
.subcategories-modal {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.summary-card {
  display: flex;
  gap: 24px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
  flex-wrap: wrap;
}

.summary-item {
  display: flex;
  flex-direction: column;
}

.label {
  font-size: 12px;
  color: #909399;
}

.value {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.subcategories-table {
  max-height: 400px;
}
</style>

