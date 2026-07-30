<template>
  <div class="page-wrapper">
    <el-card shadow="never" class="mb-3">
      <div class="header-row">
        <div>
          <h2 class="page-title">Newly Hired and Promoted</h2>
          <p class="page-subtitle">
            Shows employees onboarded this month and those with recent promotions.
          </p>
        </div>
        <div class="header-actions">
          <el-button type="primary" :loading="loading" @click="handleRefresh">
            Refresh
          </el-button>
          <el-button type="success" :loading="generating" @click="handleGenerateReport">
            Preview Report
          </el-button>
        </div>
      </div>
    </el-card>

    <el-card shadow="never">
      <el-table
        :data="tableData"
        v-loading="loading"
        border
        style="width: 100%"
        stripe
        empty-text="No newly hired or promoted employees found for this month."
      >
        <el-table-column type="index" label="#" width="60" />
        <el-table-column prop="department" label="Department" min-width="120" />
        <el-table-column prop="name" label="Name" min-width="220" />
        <el-table-column prop="position" label="Position" min-width="160" />
        <el-table-column prop="date" label="Date" min-width="140">
          <template #default="{ row }">
            <span>{{ formatDate(row.date) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="remarks" label="Remarks" min-width="120">
          <template #default="{ row }">
            <el-tag :type="row.remarks === 'Onboarded' ? 'success' : 'warning'">
              {{ row.remarks }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="plantilla" label="Plantilla Item No." min-width="160" />
        <el-table-column prop="employment_type" label="Employment" min-width="140" />
      </el-table>
    </el-card>

    <el-card v-if="previewUrl" shadow="never" class="mt-3">
      <div class="preview-header">
        <span class="preview-title">New Hired and Promoted Report</span>
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="color: #606266; font-size: 14px; margin-right: 4px;">Download as:</span>
          <el-button type="danger" @click="handleDownloadPDF" :loading="generating">
            PDF
          </el-button>
          <el-button type="primary" @click="handleDownloadWord" :loading="generating">
            DOCX
          </el-button>
          <el-button type="success" @click="handleDownloadExcel" :loading="generating">
            EXCEL
          </el-button>
        </div>
      </div>
      <iframe
        :src="previewUrl"
        style="width: 100%; height: 600px; border: none; margin-top: 8px"
      />
    </el-card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { ElMessage } from 'element-plus'
import { useNewlyHireandPromoted } from '@/composable/useNewlyHireandPromoted'
import { newlyHiredAndPromotedApi } from '@/services/api'

const { loading, newlyHiredAndPromotedRecords, fetchNewlyHiredAndPromotedRecords } =
  useNewlyHireandPromoted()

const generating = ref(false)
const previewUrl = ref('')

const tableData = computed(() => newlyHiredAndPromotedRecords.value || [])

const formatDate = (value) => {
  if (!value) return '—'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return value
  return d.toLocaleDateString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
  })
}

const handleRefresh = () => {
  fetchNewlyHiredAndPromotedRecords()
}

const handleGenerateReport = async () => {
  try {
    generating.value = true
    const res = await newlyHiredAndPromotedApi.generatePDF()
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    // Revoke previous URL if any
    if (previewUrl.value) {
      window.URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = url
  } catch (e) {
    console.error('Failed to generate Newly Hired and Promoted PDF:', e)
    ElMessage.error('Failed to generate report.')
  } finally {
    generating.value = false
  }
}

const handleDownloadPDF = async () => {
  try {
    generating.value = true
    const res = await newlyHiredAndPromotedApi.generatePDF()
    const blob = new Blob([res.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const filename = `newly_hired_and_promoted_${new Date().toISOString().split('T')[0]}.pdf`
    
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    ElMessage.success('PDF downloaded successfully')
  } catch (e) {
    console.error('Failed to download PDF:', e)
    ElMessage.error('Failed to download PDF.')
  } finally {
    generating.value = false
  }
}

const handleDownloadWord = async () => {
  try {
    generating.value = true
    const res = await newlyHiredAndPromotedApi.generateWord()
    const blob = new Blob([res.data], { 
      type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' 
    })
    const url = window.URL.createObjectURL(blob)
    const filename = `newly_hired_and_promoted_${new Date().toISOString().split('T')[0]}.docx`
    
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    ElMessage.success('Word document downloaded successfully')
  } catch (e) {
    console.error('Failed to download Word document:', e)
    ElMessage.error('Failed to download Word document.')
  } finally {
    generating.value = false
  }
}

const handleDownloadExcel = async () => {
  try {
    generating.value = true
    const res = await newlyHiredAndPromotedApi.generateExcel()
    const blob = new Blob([res.data], { 
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
    })
    const url = window.URL.createObjectURL(blob)
    const filename = `newly_hired_and_promoted_${new Date().toISOString().split('T')[0]}.xlsx`
    
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    ElMessage.success('Excel document downloaded successfully')
  } catch (e) {
    console.error('Failed to download Excel document:', e)
    ElMessage.error('Failed to download Excel document.')
  } finally {
    generating.value = false
  }
}

onMounted(() => {
  fetchNewlyHiredAndPromotedRecords()
})
</script>

<style scoped>
.page-wrapper {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.page-title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.page-subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  color: #666;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-title {
  font-weight: 600;
  font-size: 14px;
}

@media (max-width: 768px) {
  .header-row {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>