<template>
  <el-card shadow="never">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold">Applicants for Administrator Selection</span>
        <div class="flex items-center gap-2">
          <el-input v-model="search" placeholder="Search applicant" size="small" style="width: 260px" />
          <el-button type="primary" size="small" @click="printCSForm5" :disabled="isAllAppointed">Print Appointment Form</el-button>
          <el-button size="small" text @click="$emit('close')">×</el-button>
        </div>
      </div>
    </template>

    <el-table :data="filtered" size="small" stripe v-loading="loading">
      <el-table-column prop="applicant_no" label="Applicant No" width="140" />
      <el-table-column prop="name" label="Name" />
      <el-table-column prop="hr_performance_rating" label="HRMPSB Rating" width="160">
        <template #default="{ row }">
          <span v-if="row.hr_performance_rating">{{ row.hr_performance_rating }}%</span>
          <span v-else class="text-gray-400">Not rated</span>
        </template>
      </el-table-column>
      <el-table-column prop="exam_rating" label="Exam Rating" width="120">
        <template #default="{ row }">
          <span v-if="row.exam_rating">{{ row.exam_rating }}%</span>
          <span v-else class="text-gray-400">Not rated</span>
        </template>
      </el-table-column>
      <el-table-column label="Status" width="120">
        <template #default="{ row }">
          <el-tag v-if="Number(row.application_status_id) === 6" type="success" size="small">Appointed</el-tag>
          <el-tag v-else type="info" size="small">For Selection</el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Actions" width="320" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="$emit('view-pds', row)">View PDS</el-button>
          <el-button size="small" @click="$emit('view-exam', row)">View Exam</el-button>
          <el-button size="small" @click="$emit('view-hrdd', row)">View HRMPSB</el-button>
          <el-button 
            v-if="Number(row.application_status_id) !== 6" 
            size="small" 
            type="success" 
            @click="$emit('appoint', row)">
            Appoint
          </el-button>
          <el-button v-else size="small" type="success" plain disabled>Appointed</el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- Appointment Form Print Dialog -->
    <el-dialog v-model="printDialogVisible" title="Print Appointment Form" width="500px">
      <el-form :model="printForm" label-width="120px">
        <el-form-item label="Position Title" required>
          <el-input v-model="printForm.position_title" placeholder="Enter position title" />
        </el-form-item>
        <el-form-item label="Agency Name" required>
          <el-input v-model="printForm.agency_name" placeholder="Enter agency name" />
        </el-form-item>
        <el-form-item label="Location" required>
          <el-input v-model="printForm.location" placeholder="Enter location" />
        </el-form-item>
        <el-form-item label="Author" required>
          <el-input v-model="printForm.author" placeholder="Enter author name" />
        </el-form-item>
        <el-form-item label="Date" required>
          <el-date-picker 
            v-model="printForm.date" 
            type="date" 
            placeholder="Select date"
            format="YYYY-MM-DD"
            value-format="YYYY-MM-DD"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="printDialogVisible = false">Cancel</el-button>
          <el-button type="primary" @click="handlePrint" :loading="printLoading">Print</el-button>
        </div>
      </template>
    </el-dialog>
  </el-card>
</template>

<script setup>
import { computed, ref } from 'vue'
import { adminSelectApi } from '@/services/api'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  position: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['view-pds', 'view-exam', 'view-hrdd', 'appoint', 'close'])

const search = ref('')
const printDialogVisible = ref(false)
const printLoading = ref(false)

const printForm = ref({
  position_title: '',
  agency_name: '',
  location: '',
  author: '',
  date: ''
})

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim()
  if (!q) return props.items
  return props.items.filter((r) => 
    String(r.name).toLowerCase().includes(q) || 
    String(r.applicant_no).includes(q)
  )
})

// Disable CS Form 5 when all applicants are already appointed
const isAllAppointed = computed(() => {
  const rows = filtered.value || []
  if (!rows.length) return true
  return rows.every(r => Number(r.application_status_id) === 5)
})

const printCSForm5 = () => {
  // Auto-fill position title based on selected plantilla position
  const title = props.position?.position || props.position?.name || props.position?.code || ''
  if (title) {
    printForm.value.position_title = title
  }
  printDialogVisible.value = true
}

const handlePrint = async () => {
  printLoading.value = true
  try {
    // Ensure position title auto-fills if still empty
    if (!printForm.value.position_title) {
      const title = props.position?.position || props.position?.name || props.position?.code || ''
      printForm.value.position_title = title
    }

    const response = await adminSelectApi.print(printForm.value)
    
    // Create blob and download
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `CS_Form5_Certificate_${(printForm.value.position_title || 'Position')}_${printForm.value.date}.pdf`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    printDialogVisible.value = false
  } catch (error) {
    console.error('Print error:', error)
  } finally {
    printLoading.value = false
  }
}
</script>

<style scoped>
.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.gap-2 { gap: 8px; }
.font-bold { font-weight: 700; }
.text-gray-400 { color: #9ca3af; }
</style>
