<template>
  <PageScaffold title="Acceptance of Retirement" subtitle="Generate acceptance of retirement letter">
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-6">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="180px">
          <el-form-item label="Employee" prop="employee">
            <el-select v-model="formData.employee" placeholder="Select off-boarded employee" filterable clearable class="w-full">
              <el-option v-for="emp in employees" :key="emp.id" :label="emp.name" :value="emp.id" />
            </el-select>
          </el-form-item>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <el-form-item label="Date" prop="date">
              <el-date-picker v-model="formData.date" type="date" placeholder="Select date" class="w-full"/>
            </el-form-item>
            <el-form-item label="Effectivity Date" prop="effectivity_date">
              <el-date-picker v-model="formData.effectivity_date" type="date" placeholder="Select effectivity date" class="w-full"/>
            </el-form-item>
            <el-form-item label="Signatory" prop="signatory">
              <el-input v-model="formData.signatory" placeholder="Enter signatory name" enabled />
            </el-form-item>
            <el-form-item label="Position" prop="position1">
              <el-input v-model="formData.position1" placeholder="Enter signatory position" enabled/>
            </el-form-item>
            <el-form-item label="Received Date" prop="received_date">
              <el-date-picker v-model="formData.received_date" type="date" placeholder="Select received date" class="w-full"/>
            </el-form-item>
            <el-form-item label="Received By" prop="received_signatory">
              <el-input v-model="formData.received_signatory" placeholder="Enter received by name" />
            </el-form-item>
          </div>

    <div class="flex justify-end gap-3 mt-2">
      <el-button @click="reset">Reset</el-button>
      <el-button type="primary" :loading="generateLoading" @click="onPreview('pdf')">Preview PDF</el-button>
    </div>
        </el-form>
      </div>
    </div>

  <!-- Inline Preview Section -->
  <div v-if="showPreview" class="mt-6 bg-white rounded-lg shadow p-4">
    <div class="flex items-center justify-between mb-3">
      <div>
        <div class="text-base font-semibold">Acceptance of Retirement - {{ selectedEmployeeName }}</div>
        <div class="text-xs text-gray-500">Preview below reflects your latest inputs</div>
      </div>
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-600 mr-2">Download as:</span>
        <el-button
          type="danger"
          size="small"
          @click="downloadPdf"
          :loading="generateLoading"
        >
          PDF
        </el-button>
        <el-button
          type="primary"
          size="small"
          @click="downloadWord"
          :loading="generateLoading"
        >
          Word
        </el-button>
      </div>
    </div>

    <div class="border rounded overflow-hidden" style="height:75vh;">
      <iframe
        v-if="pdfUrl || wordUrl"
        :src="previewType === 'pdf' ? pdfUrl : pdfUrl"  
        class="w-full h-full border-0"
      ></iframe>
      <div v-else class="p-6 text-center text-gray-500">No preview available</div>
    </div>
  </div>
  </PageScaffold>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import PageScaffold from '../PageScaffold.vue'
import { useAcceptanceOfRetirement } from '../../composable/useAcceptanceOfRetirement.js'

const { loading, generateLoading, employees, fetchEmployees, generateAcceptancePdf, generateAcceptanceWord, previewAcceptanceWord, downloadPDFFromBlob, downloadWordFromBlob } = useAcceptanceOfRetirement()

const formRef = ref()
const formData = ref({
  employee: '',
  date: '',
  effectivity_date: '',
  signatory: 'MA. FE J. AVILA',
  position1: 'OIC- Executive Director',
  received_date: '',
  received_signatory: ''
})

const validateEffectivityDate = (rule, value, callback) => {
  if (!value) {
    callback(new Error('Effectivity date is required'))
    return
  }
  
  // Check if effectivity date is not before received date
  if (formData.value.received_date && new Date(value) < new Date(formData.value.received_date)) {
    callback(new Error('Effectivity date cannot be before received date'))
    return
  }
  
  // Check if effectivity date is not before acceptance date
  if (formData.value.date && new Date(value) < new Date(formData.value.date)) {
    callback(new Error('Effectivity date cannot be before acceptance date'))
    return
  }
  
  callback()
}

const rules = {
  employee: [{ required: true, message: 'Please select employee', trigger: 'change' }],
  date: [{ required: true, message: 'Date is required', trigger: 'change' }],
  effectivity_date: [
    { required: true, message: 'Effectivity date is required', trigger: 'change' },
    { validator: validateEffectivityDate, trigger: 'change' }
  ],
  signatory: [{ required: true, message: 'Signatory is required', trigger: 'blur' }],
  position1: [{ required: true, message: 'Position is required', trigger: 'blur' }],
  received_date: [{ required: true, message: 'Received date is required', trigger: 'change' }],
  received_signatory: [{ required: true, message: 'Received by is required', trigger: 'blur' }]
}

const showPreview = ref(false)
const pdfUrl = ref('')
const wordUrl = ref('')
const previewType = ref('pdf') // 'pdf' or 'word'

const selectedEmployeeName = computed(() => employees.value.find(e => e.id === formData.value.employee)?.name || 'Unknown')

const onPreview = async (type = 'pdf') => {
  await formRef.value?.validate()

  if (type === 'pdf') {
    // Standard PDF preview
    const url = await generateAcceptancePdf({ ...formData.value })
    pdfUrl.value = url
    wordUrl.value = ''
    previewType.value = 'pdf'
  } else {
    // For Word preview: generate Word for download, and PDF for iframe preview
    const [wordObjectUrl, pdfObjectUrl] = await Promise.all([
      previewAcceptanceWord({ ...formData.value }),
      generateAcceptancePdf({ ...formData.value })
    ])
    wordUrl.value = wordObjectUrl
    pdfUrl.value = pdfObjectUrl
    // Force iframe to show PDF for reliable in-browser preview
    previewType.value = 'pdf'
  }

  showPreview.value = true
}

const closePreview = () => {
  // no-op (modal removed); keep for potential reuse
}

const downloadPdf = () => {
  if (!pdfUrl.value) return
  fetch(pdfUrl.value).then(r => r.blob()).then(blob => {
    downloadPDFFromBlob(blob, `acceptance_of_retirement_${selectedEmployeeName.value.replace(/\s+/g, '_')}.pdf`)
  })
}

const downloadWord = async () => {
  try {
    const blob = await generateAcceptanceWord({ ...formData.value })
    downloadWordFromBlob(blob, `acceptance_of_retirement_${selectedEmployeeName.value.replace(/\s+/g, '_')}.docx`)
  } catch (error) {
    console.error('Failed to download Word document:', error)
  }
}

const reset = () => {
  formData.value = { 
    employee: '', 
    date: '', 
    effectivity_date: '', 
    signatory: 'MA. FE J. AVILA', 
    position1: 'OIC- Executive Director', 
    received_date: '', 
    received_signatory: '' 
  }
  formRef.value?.resetFields()
}

// Watchers to re-validate effectivity date when related dates change
watch(() => [formData.value.received_date, formData.value.date], () => {
  if (formData.value.effectivity_date) {
    formRef.value?.validateField('effectivity_date')
  }
})

onMounted(async () => {
  await fetchEmployees()
})
</script>

<style scoped>
.w-full { width: 100%; }
</style>

