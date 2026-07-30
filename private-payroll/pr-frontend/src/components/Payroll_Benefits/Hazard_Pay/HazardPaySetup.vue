<template>
  <div class="hazard-pay-setup">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>Hazard Pay Setup Table</span>
          <el-button type="primary" @click="handleAddRow" :loading="loading">
            <el-icon><Plus /></el-icon>
            Add Row
          </el-button>
        </div>
      </template>

      <div class="setup-content">
        <p class="description">
          Configure hazard pay percentages based on salary ranges. Employees will receive hazard pay based on their salary and the number of days worked in hazardous conditions.
        </p>

        <el-table
          :data="setupData"
          style="width: 100%"
          v-loading="loading"
          class="setup-table"
        >
          <el-table-column label="Salary From" width="150">
            <template #default="{ row }">
              <el-input-number
                v-model="row.salary_from"
                :min="0"
                :max="1000000000"
                :precision="2"
                size="small"
                style="width: 100%"
                placeholder="0.00"
              />
            </template>
          </el-table-column>

          <el-table-column label="Salary To" width="150">
            <template #default="{ row }">
              <el-input-number
                v-model="row.salary_to"
                :min="0"
                :max="1000000000"
                :precision="2"
                size="small"
                style="width: 100%"
                placeholder="0.00"
              />
            </template>
          </el-table-column>

          <el-table-column label="Percentage (%)" width="150">
            <template #default="{ row }">
              <el-input-number
                v-model="row.percentage"
                :min="0.01"
                :max="100"
                :precision="2"
                size="small"
                style="width: 100%"
                placeholder="0.00"
              />
            </template>
          </el-table-column>

          <el-table-column label="Actions" width="100" align="center">
            <template #default="{ $index }">
              <div class="actions-cell">
                <el-button
                  type="danger"
                  size="small"
                  @click="removeRow($index)"
                  :loading="loading"
                  :disabled="setupData.length <= 1"
                >
                  <el-icon><Delete /></el-icon>
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>

        <div class="setup-actions">
          <el-button type="success" @click="handleSave" :loading="loading" :disabled="!isValid">
            <el-icon><Check /></el-icon>
            Save Setup
          </el-button>
          <el-button @click="handleReset" :loading="loading">
            <el-icon><Refresh /></el-icon>
            Reset
          </el-button>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ElMessage } from 'element-plus';
import { Plus, Delete, Check, Refresh } from '@element-plus/icons-vue/dist/types';
import { useHazardPay } from '@/Composables/useHazardPay';

const {
  loading,
  hazardPaySetup,
  loadHazardPaySetup,
  saveHazardPaySetup,
  deleteHazardPaySetup,
  validateHazardPaySetup
} = useHazardPay();

// Local state
const setupData = ref([]);

// Computed
const isValid = computed(() => {
  return setupData.value.every(row => 
    row.salary_from > 0 && 
    row.salary_to > 0 && 
    row.percentage > 0 &&
    row.salary_from <= row.salary_to
  );
});

// Methods
const loadSetupData = async () => {
  try {
    await loadHazardPaySetup();
    setupData.value = hazardPaySetup.value.length > 0 
      ? [...hazardPaySetup.value] 
      : [createEmptyRow()];
  } catch (error) {
    console.error('Error loading setup data:', error);
    setupData.value = [createEmptyRow()];
  }
};

const createEmptyRow = () => ({
  id: 0,
  salary_from: 0,
  salary_to: 0,
  percentage: 0
});

const handleAddRow = () => {
  setupData.value.push(createEmptyRow());
};

const removeRow = (index) => {
  if (setupData.value.length > 1) {
    setupData.value.splice(index, 1);
  }
};

const handleSave = async () => {
  try {
    // Validate all rows
    const errors = [];
    setupData.value.forEach((row, index) => {
      const rowErrors = validateHazardPaySetup(row);
      if (rowErrors.length > 0) {
        errors.push(`Row ${index + 1}: ${rowErrors.join(', ')}`);
      }
    });

    if (errors.length > 0) {
      ElMessage.error(errors.join('; '));
      return;
    }

    // Prepare data for backend
    const data = {
      id: setupData.value.map(row => row.id),
      salary_from: setupData.value.map(row => row.salary_from),
      salary_to: setupData.value.map(row => row.salary_to),
      percentage: setupData.value.map(row => row.percentage)
    };

    await saveHazardPaySetup(data);
    await loadSetupData(); // Reload to get updated IDs
  } catch (error) {
    console.error('Error saving setup:', error);
  }
};

const handleReset = async () => {
  await loadSetupData();
};

// Lifecycle
onMounted(() => {
  loadSetupData();
});
</script>

<style scoped>
.hazard-pay-setup {
  max-width: 1000px;
  margin: 0 auto;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.setup-content {
  padding: 20px 0;
}

.description {
  color: #606266;
  margin-bottom: 20px;
  line-height: 1.6;
  padding: 15px;
  background-color: #f0f9ff;
  border-left: 4px solid #409eff;
  border-radius: 4px;
}

.setup-table {
  margin-bottom: 20px;
}

.setup-actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  padding-top: 20px;
  border-top: 1px solid #e4e7ed;
}

:deep(.el-table th) {
  background-color: #f5f7fa;
  font-weight: 600;
}

:deep(.el-input-number) {
  width: 100%;
}

:deep(.el-input-number .el-input__inner) {
  text-align: center;
}

.actions-cell {
  display: flex;
  gap: 6px;
  align-items: center;
  justify-content: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.actions-cell :deep(.el-button__content) {
  white-space: nowrap;
}
</style>
