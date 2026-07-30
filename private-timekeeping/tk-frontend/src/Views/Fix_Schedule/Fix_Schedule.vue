<template>
  <PageScaffold
    title="Fix Schedule Management"
    subtitle="Configure and manage fixed work schedules for employees"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Fix Schedule' }]"
  >
    <template #actions>
      <div class="flex flex-col items-end gap-2">
        <el-button type="primary" @click="openForm()" style="width: max-content;">New Schedule</el-button>
      </div>
    </template>
    <div class="mb-3 flex items-center gap-2">
      <EmployeeSearchbar
        v-model="query"
        :loading="loading"
        :placeholder="'Search schedules...'"
        :show-advanced-filters="false"
        @search="handleSearch"
        @reload="reload"
      />
      <div class="flex-1"></div>
      <PreviewExport
        :html-content="reportHtmlContent"
        :title="'Fix Schedule Report'"
        :filename="'fix_schedule_report'"
        :on-excel="handleExportExcel"
        :on-pdf="handleExportPDF"
        :on-word="handleExportWord"
        :loading="loading || htmlLoading"
      />
    </div>

    <FixScheduleTable :items="items" :query="query" :loading="loading" @view="onView" @edit="onEdit" @assigned="onAssigned" @delete="onDelete" />

    <FixScheduleForm ref="formRef" v-model="formOpen" :edit-id="editId" @saved="reload" />
    <FixScheduleViewer v-model="viewerOpen" :view-id="viewId" />
    <FixScheduleAssigned v-model="assignedOpen" :schedule-id="assignedId" :schedule-name="assignedName" />
  </PageScaffold>
  
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import EmployeeSearchbar from '../../components/Reusable_Components/EmployeeSearchbar.vue'
import FixScheduleTable from '../../components/Fix_Schedule/FixScheduleTable.vue'
import FixScheduleForm from '../../components/Fix_Schedule/FixScheduleForm.vue'
import FixScheduleViewer from '../../components/Fix_Schedule/FixScheduleViewer.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import { fixScheduleService } from '../../services/api'
import { onMounted, ref, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import FixScheduleAssigned from '../../components/Fix_Schedule/FixScheduleAssigned.vue'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const query = ref('')
const items = ref([])
const loading = ref(false)
const formOpen = ref(false)
const editId = ref(0)
const formRef = ref(null)
const viewerOpen = ref(false)
const viewId = ref(0)
const assignedOpen = ref(false)
const assignedId = ref(0)
const assignedName = ref('')

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')

const handleSearch = (searchValue) => {
  query.value = searchValue
}

// Load HTML preview from backend
async function loadHtmlPreview() {
  if (!items.value || !Array.isArray(items.value) || !items.value.length) {
    reportHtmlContent.value = ''
    return
  }
  reportHtmlContent.value = await getHtmlPreview('fix_schedule_list', { schedules: items.value || [] })
}

// Watch items to reload preview
watch(items, () => {
  loadHtmlPreview()
}, { deep: true })

onMounted(() => {
  loadHtmlPreview()
})

async function handleExportExcel() {
  await exportToExcel('fix_schedule_list', { schedules: items.value || [] }, 'fix_schedule_report')
}

async function handleExportWord() {
  await exportToWord('fix_schedule_list', { schedules: items.value || [] }, 'fix_schedule_report')
}

async function handleExportPDF() {
  // Use backend HTML to generate PDF
  const html = await getHtmlPreview('fix_schedule_list', { schedules: items.value || [] })
  if (!html) return
  
  // Use the existing PDF generation from HTML
  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, 'fix_schedule_report')
}

async function reload() {
  loading.value = true
  try {
    const response = await fixScheduleService.list()
    
    // Ensure items is always an array
    if (Array.isArray(response)) {
      items.value = response
    } else if (response && Array.isArray(response.data)) {
      items.value = response.data
    } else if (response && Array.isArray(response.items)) {
      items.value = response.items
    } else {
      items.value = []
    }
  } catch (err) {
    console.error('Fix Schedule API Error:', err)
    items.value = [] // Ensure items is always an array
    ElMessage.error(err?.message || 'Failed to load fix schedules')
  } finally {
    loading.value = false
  }
}

function openForm(id = 0) { editId.value = Number(id || 0); formOpen.value = true }
function onEdit(row) { openForm(row.id) }
function onView(row) { viewId.value = Number(row.id); viewerOpen.value = true }
function onAssigned(row) { assignedId.value = Number(row.id); assignedName.value = row.name || ''; assignedOpen.value = true }

async function onDelete(row) {
  try {
    await ElMessageBox.confirm(
      `Delete schedule "${row.name}"? This action cannot be undone.`,
      'Confirm Deletion',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' }
    )
  } catch (_) {
    return
  }
  try {
    loading.value = true
    await fixScheduleService.destroy(row.id)
    ElMessage.success('Fix schedule deleted')
    await reload()
  } catch (err) {
    console.error(err)
    ElMessage.error(err?.message || 'Failed to delete fix schedule')
  } finally {
    loading.value = false
  }
}

onMounted(reload)
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
</style>

