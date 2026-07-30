<template>
  <PageScaffold
    title="Export Employee Data"
    subtitle="Export comprehensive employee datasets for reporting and analytics"
  >
    <ExportDataList
      :employee-data="employeeData"
      :loading="loading"
      :export-progress="exportProgress"
      @export="onExport"
      @refresh="reload"
    />

    <!-- PDF Preview (reuse certificate-style modal) -->
    <CertificatePreviewModal
      v-if="previewVisible"
      :visible="previewVisible"
      :pdf-url="previewPdfUrl"
      :word-url="''"
      preview-type="pdf"
      :employee-name="previewTitle"
      certificate-type="Employee Data Export"
      :loading="previewLoading"
      :preview-key="previewKey"
      :show-purpose-edit="false"
      :show-pdf-button="false"
      :show-word-button="false"
      :show-excel-button="true"
      :show-csv-button="true"
      @close="handlePreviewClose"
      @download="handlePreviewDownload"
      @download-excel="handlePreviewDownloadExcel"
      @download-csv="handlePreviewDownloadCsv"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import ExportDataList from '@/components/Export_employee_data/ExportDataList.vue'
import CertificatePreviewModal from '@/components/Certificate/CertificatePreviewModal.vue'
import { useExportEmployeeData } from '@/composable/useExportEmployeeData'
import { ElMessage } from 'element-plus'

const { 
  loading, 
  employeeData, 
  exportProgress,
  fetchEmployeeData,
  exportData,
  getPdfPreviewUrl,
  exportToPDF,
  exportToExcel,
  exportToCSV
} = useExportEmployeeData()

const previewVisible = ref(false)
const previewPdfUrl = ref('')
const previewLoading = ref(false)
const today = new Date()
const formattedDate = today.toISOString().split('T')[0] 
const previewKey = ref(0)
const previewTitle = ref('All Employees')
const lastPdfPayload = ref({ data: [], filename: `Employee_Data_${formattedDate}.pdf` })

const revokePreviewUrlIfAny = () => {
  if (previewPdfUrl.value && previewPdfUrl.value.startsWith('blob:')) {
    try {
      URL.revokeObjectURL(previewPdfUrl.value)
    } catch {
      // ignore
    }
  }
}

// Methods
const reload = async () => {
  try {
    await fetchEmployeeData()
  } catch (error) {
    console.error('Failed to reload employee data:', error)
  }
}

const onExport = async (exportConfig) => {
  try {
    const { format, data, filename } = exportConfig
    
    if (!data || data.length === 0) {
      ElMessage.warning('No data to export')
      return
    }

    if (String(format).toLowerCase() === 'pdf') {
      // Prepare preview instead of immediate export
      previewLoading.value = true
      revokePreviewUrlIfAny()

      const url = getPdfPreviewUrl(data)
      if (!url) {
        previewLoading.value = false
        ElMessage.error('Failed to generate PDF preview')
        return
      }

      previewPdfUrl.value = url
      previewTitle.value = `Employees (${data.length})`
      lastPdfPayload.value = {
        data,
        filename: filename || `Employee_Data_${formattedDate}.pdf`
      }
      previewKey.value += 1
      previewVisible.value = true
      previewLoading.value = false
      return
    }
    
    // Non-PDF formats: keep existing export behavior
    await exportData(format, data, filename)
    ElMessage.success(`Export completed: ${filename}`)
  } catch (error) {
    console.error('Export failed:', error)
    ElMessage.error('Export failed. Please try again.')
  }
}

const handlePreviewClose = () => {
  previewVisible.value = false
  revokePreviewUrlIfAny()
  previewPdfUrl.value = ''
}

const handlePreviewDownload = async () => {
  // For Employee Data Export, we already have a rendered preview (blob URL).
  // Download directly from the existing preview instead of opening a new window.
  const { data, filename } = lastPdfPayload.value || {}
  if (!data || data.length === 0) {
    ElMessage.warning('No data to export')
    return
  }

  if (!previewPdfUrl.value) {
    ElMessage.error('Preview is not available for download')
    return
  }

  try {
    const link = document.createElement('a')
    link.href = previewPdfUrl.value
    link.download = filename || `Employee_Data_${formattedDate}.pdf`
    link.style.display = 'none'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error('PDF download from preview failed:', error)
    // Fallback to previous export behavior if direct download fails
    try {
      await exportToPDF(data, filename || `Employee_Data_${formattedDate}.pdf`)
    } catch (fallbackError) {
      console.error('Fallback PDF export also failed:', fallbackError)
    }
  }
}

const handlePreviewDownloadExcel = async () => {
  const { data } = lastPdfPayload.value || {}
  if (!data || data.length === 0) {
    ElMessage.warning('No data to export')
    return
  }
  try {
    await exportToExcel(data, 'employee_data.xlsx')
  } catch (error) {
    console.error('Excel export from preview failed:', error)
  }
}

const handlePreviewDownloadCsv = async () => {
  const { data } = lastPdfPayload.value || {}
  if (!data || data.length === 0) {
    ElMessage.warning('No data to export')
    return
  }
  try {
    await exportToCSV(data, 'employee_data.csv')
  } catch (error) {
    console.error('CSV export from preview failed:', error)
  }
}

// Initialize
onMounted(() => {
  reload()
})
</script>

<style scoped>
/* Add any custom styles here */
</style>

