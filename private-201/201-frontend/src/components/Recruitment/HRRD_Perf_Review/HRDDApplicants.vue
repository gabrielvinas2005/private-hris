<template>
  <el-card shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Applicants</span>
        <div class="flex items-center gap-2">
          <el-input v-model="search" placeholder="Search applicant" size="small" style="width: 260px" />
          <el-button type="primary" size="small" @click="$emit('save')">Save</el-button>
        </div>
      </div>
    </template>
    <el-table :data="filtered" size="small" stripe>
      <el-table-column prop="applicant_no" label="Applicant No" width="140" />
      <el-table-column prop="name" label="Name" />
      <el-table-column label=" Rating" width="160">
        <template #default="{ row, $index }">
          <el-input-number v-model="ratings[$index]" :min="0" :max="100" size="small" />
        </template>
      </el-table-column>
      <el-table-column label="Decision" width="180">
        <template #default="{ row }">
          <el-radio-group
            v-model="decisions[row.applicant_id]"
            size="small"
            @change="(val) => onDecisionChange(row.applicant_id, val)"
          >
            <el-radio-button label="accept">Accept</el-radio-button>
            <el-radio-button label="reject">Reject</el-radio-button>
          </el-radio-group>
        </template>
      </el-table-column>
      <!-- <el-table-column label="Attachments" width="240">
        <template #default="{ row }">
          <input type="file" multiple @change="onAttach($event, row.applicant_id)" />
        </template>
      </el-table-column> -->
      <el-table-column label="Deliberation Documents" width="280">
        <template #default="{ row }">
          <input type="file" multiple @change="onBIAttach($event, row.applicant_id)" />
        </template>
      </el-table-column>
    </el-table>
  </el-card>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] }
})
const emit = defineEmits(['save', 'decision'])

const search = ref('')
const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter((r) => String(r.name).toLowerCase().includes(q) || String(r.applicant_no).includes(q))
})

const ratings = ref([])
const selected = reactive({})
const decisions = reactive({})
const attachments = reactive({})
const biAttachments = reactive({})

watch(() => props.items, (v) => {
  const items = v || []
  ratings.value = items.map((r) => r.hr_performance_rating ?? 0)

  // Initialize per-applicant decisions without clobbering existing user input mid-edit.
  items.forEach((r) => {
    const id = r.applicant_id
    if (decisions[id] === undefined) decisions[id] = null
    if (selected[id] === undefined) selected[id] = false
  })

  console.log('HRDDApplicants: ratings updated:', ratings.value)
}, { immediate: true })

const onDecisionChange = (applicantId, val) => {
  // Backend expects `select[]` only for accepted (forwarded) applicants.
  // Keep `selected` as a boolean map for backward compatibility with parent save logic.
  const decision = val ?? decisions[applicantId]
  selected[applicantId] = decision === 'accept'

  // Let parent decide how to persist (auto-forward / auto-reject).
  emit('decision', { applicantId, decision })
}

const onAttach = (e, id) => {
  const files = Array.from(e.target.files || [])
  if (files.length > 0) {
    attachments[id] = files
  }
}

const onBIAttach = (e, id) => {
  const files = Array.from(e.target.files || [])
  if (files.length > 0) {
    biAttachments[id] = files
  }
}

// expose state up to parent when saving
defineExpose({ 
  ratings, 
  selected, 
  decisions,
  attachments,
  biAttachments
})
</script>

<style scoped>
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 8px; }
.font-bold { font-weight: 700; }
</style>


