<template>
  <div class="filters-container">
    <div class="filters-row">
      <div class="left">
        <div class="info-section">
          <h4>Monetization Setup</h4>
          <p class="description">Configure the conversion factor rate and maximum number of leave credits allowed for monetization.</p>
        </div>
      </div>
      <div class="actions">
        <el-button type="primary" @click="handleEdit">Edit Setup</el-button>
      </div>
    </div>
  </div>
</template>

<script setup>
const emit = defineEmits(['edit'])

function handleEdit() {
  emit('edit')
}
</script>

<style scoped>
.filters-container {
  width: 100%;
}

.filters-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left {
  display: flex;
  gap: 10px;
  align-items: center;
}

.info-section h4 {
  margin: 0 0 4px 0;
  color: #409eff;
  font-size: 16px;
  font-weight: 600;
}

.info-section .description {
  margin: 0;
  color: #606266;
  font-size: 14px;
  line-height: 1.4;
}

.actions {
  display: flex;
  gap: 8px;
}
</style>
