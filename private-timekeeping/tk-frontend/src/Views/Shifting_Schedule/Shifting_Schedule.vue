<template>
  <PageScaffold
    title="Shifting Schedule Management"
    subtitle="Set up and manage rotating shift schedules and assignments"
    :breadcrumbs="[{ label: 'Timekeeping Module', to: '/' }, { label: 'Shifting Schedule' }]"
  >
    <template #actions>
      <div class="flex items-center gap-2">
        <el-button type="primary" @click="openForm()" style="width: max-content;">New Shift Schedule</el-button>
      </div>
    </template>
    <div class="mb-3 flex items-center gap-2">
      <el-input v-model="query" placeholder="Search schedules..." clearable style="max-width: 300px" />
      <div class="flex-1"></div>
      <PreviewExport
        :html-content="reportHtmlContent"
        :title="'Shift Schedule Report'"
        :filename="computedFilename"
        :on-excel="handleExportExcel"
        :on-pdf="handleExportPDF"
        :on-word="handleExportWord"
        :loading="loading || htmlLoading"
      />
    </div>

    <ShiftScheduleTable :items="items" :query="query" :loading="loading" @view="onView" @edit="onEdit" @assign="onAssign" @delete="onDelete" />

    <ShiftScheduleForm v-model="formOpen" :edit-id="editId" @saved="reload" />
    <ShiftScheduleAssign v-model="assignOpen" :header-id="assignId" @updated="reload" />
    <ShiftScheduleViewer v-model="viewerOpen" :view-id="viewId" />
    
  </PageScaffold>
</template>

<script setup>
import PageScaffold from '../../components/Reusable_Components/PageScaffold.vue'
import ShiftScheduleTable from '../../components/Shifting_Schedule/ShiftScheduleTable.vue'
import ShiftScheduleForm from '../../components/Shifting_Schedule/ShiftScheduleForm.vue'
import ShiftScheduleAssign from '../../components/Shifting_Schedule/ShiftScheduleAssign.vue'
import ShiftScheduleViewer from '../../components/Shifting_Schedule/ShiftScheduleViewer.vue'
import PreviewExport from '../../components/Reusable_Components/Preview&Export.vue'
import { shiftScheduleService } from '../../services/api'
import { onMounted, ref, computed, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { useBackendReportExport } from '../../Composables/useBackendReportExport'

const query = ref('')
const items = ref([])
const loading = ref(false)
const formOpen = ref(false)
const editId = ref(0)
const assignOpen = ref(false)
const assignId = ref(0)
const viewerOpen = ref(false)
const viewId = ref(0)

const { exportToExcel, exportToWord, getHtmlPreview, htmlLoading } = useBackendReportExport()
const reportHtmlContent = ref('')
const computedFilename = computed(() => `shift_schedule_report_${new Date().toISOString().slice(0, 10)}`)

// Filtered items for export
const filteredItems = computed(() => {
  const q = (query.value || '').toLowerCase().trim()
  if (!q) return items.value
  return items.value.filter(i => String(i.name || '').toLowerCase().includes(q))
})

// Load HTML preview from backend
async function loadHtmlPreview() {
  if (!filteredItems.value || !filteredItems.value.length) {
    reportHtmlContent.value = ''
    return
  }
  reportHtmlContent.value = await getHtmlPreview('shift_schedule_list', { schedules: filteredItems.value || [] })
}

// Watch items to reload preview
watch([filteredItems], () => {
  loadHtmlPreview()
}, { deep: true })

async function handleExportExcel() {
  await exportToExcel('shift_schedule_list', { schedules: filteredItems.value || [] }, computedFilename.value)
}

async function handleExportWord() {
  await exportToWord('shift_schedule_list', { schedules: filteredItems.value || [] }, computedFilename.value)
}

async function handleExportPDF() {
  const html = await getHtmlPreview('shift_schedule_list', { schedules: filteredItems.value || [] })
  if (!html) return
  
  const { useReportGenerator } = await import('../../Composables/useReportGenerator')
  const { exportToPDF } = useReportGenerator()
  await exportToPDF(html, computedFilename.value)
}

async function reload() {
  loading.value = true
  try {
    items.value = await shiftScheduleService.list()
  } catch (err) {
    console.error(err)
    ElMessage.error(err?.message || 'Failed to load shift schedules')
  } finally {
    loading.value = false
  }
}

// Helper function to format dates
function fmt(d) {
  if (!d) return ''
  const dt = new Date(d)
  if (isNaN(dt)) return d
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December']
  return `${months[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()}`
}

function openForm(id = 0) { editId.value = Number(id || 0); formOpen.value = true }
function onEdit(row) { openForm(row.id) }
function onView(row) { viewId.value = Number(row.id); viewerOpen.value = true }
function onAssign(row) { assignId.value = Number(row.id); assignOpen.value = true }

async function onDelete(row) {
  try {
    await ElMessageBox.confirm(
      `Delete shift schedule "${row.name}"? This action cannot be undone.`,
      'Confirm Deletion',
      { type: 'warning', confirmButtonText: 'Delete', cancelButtonText: 'Cancel' }
    )
  } catch (_) {
    return
  }
  try {
    loading.value = true
    await shiftScheduleService.destroy(row.id)
    ElMessage.success('Shift schedule deleted')
    await reload()
  } catch (err) {
    console.error(err)
    ElMessage.error(err?.message || 'Failed to delete shift schedule')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await reload()
  await loadHtmlPreview()
})
</script>

<style scoped>
.mb-3 { margin-bottom: 12px; }
.flex-1 { flex: 1; }
</style>

