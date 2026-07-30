<template>
  <el-dialog
    v-model="visible"
    :title="`DPCR Details - ${dpcrData?.division || 'N/A'}`"
    width="90%"
    :close-on-click-modal="false"
  >
    <div v-loading="loading" class="dpcr-view">
      <div v-if="dpcrData" class="space-y-6">
        <!-- DPCR Header Information -->
        <el-card shadow="never">
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-semibold">DPCR Information</h3>
              <el-tag :type="getStatusType(dpcrData)" size="large">
                {{ getStatusText(dpcrData) }}
              </el-tag>
            </div>
          </template>
          
          <el-row :gutter="20">
            <el-col :span="6">
              <div class="info-item">
                <label>Division:</label>
                <span>{{ dpcrData.division || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Year:</label>
                <span>{{ dpcrData.year || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Period From:</label>
                <span>{{ dpcrData.month_from || 'N/A' }}</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="info-item">
                <label>Period To:</label>
                <span>{{ dpcrData.month_to || 'N/A' }}</span>
              </div>
            </el-col>
          </el-row>
        </el-card>
      </div>
    </div>

    <template #footer>
      <div class="dialog-footer">
        <el-button @click="onClose">Close</el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  dpcrData: { type: Object, default: null },
  loading: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const getStatusType = (data) => {
  return 'info'
}

const getStatusText = (data) => {
  return 'Active'
}

const onClose = () => {
  visible.value = false
}
</script>

<style scoped>
.dpcr-view {
  padding: 0;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item label {
  font-weight: 600;
  color: #606266;
  font-size: 14px;
}

.info-item span {
  color: #303133;
  font-size: 14px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style>
