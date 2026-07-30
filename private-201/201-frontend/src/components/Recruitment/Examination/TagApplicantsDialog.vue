<template>
  <el-dialog :model-value="visible" title="Tag Shortlisted Applicants" width="900px" @update:model-value="$emit('update:visible', $event)">
    <div class="mb-2 font-bold">Shortlisted Applicants</div>
    
    <div v-if="applicants.length === 0" class="text-center py-8 text-gray-500">
      <el-icon size="48" class="mb-2"><User /></el-icon>
      <p>No shortlisted applicants available for this examination.</p>
      <p class="text-sm">Applicants must be shortlisted first before they can be tagged for examination.</p>
    </div>
    
    <el-table v-else :data="applicants" @selection-change="onSelect" size="small" stripe>
      <el-table-column type="selection" width="50" />
      <el-table-column prop="applicant_no" label="Applicant No." width="140" />
      <el-table-column prop="name" label="Name" />
    </el-table>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="$emit('update:visible', false)">Close</el-button>
        <el-button type="primary" :loading="saving" @click="onSave">Save</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup>
import { ref } from 'vue'
import { User } from '@element-plus/icons-vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  scheduleId: { type: Number, default: 0 },
  applicants: { type: Array, default: () => [] },
  saving: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible', 'save'])

const selected = ref([])

const onSelect = (rows) => {
  selected.value = rows
}

const onSave = () => {
  const ids = props.applicants.map(a => a.id)
  const select = props.applicants.map(a => selected.value.some(s => s.id === a.id) ? a.id : null).filter(Boolean)
  emit('save', { ids, select })
}
</script>

<style scoped>
.dialog-footer { display: inline-flex; gap: 8px; }
.mb-2 { margin-bottom: .5rem; }
.font-bold { font-weight: 700; }
</style>


