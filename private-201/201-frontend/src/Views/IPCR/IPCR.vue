<template>
  <PageScaffold
    title="IPCR"
    subtitle="Individual Performance Commitment and Review"
  >
    <IPCRList
      :ipcr-ratings="ipcrRatings"
      :loading="loading"
      @refresh="reload"
      @add="onAdd"
      @view="onView"
      @edit="onEdit"
      @review="onReview"
      @delete="onDelete"
    />

    <IPCRForm
      v-model="showForm"
      :ipcr-data="selectedIPCR"
      :form-data="formData"
      :loading="formLoading"
      @save="onSave"
    />

    <EmployeeReview
      v-model="showReview"
      :review-data="reviewData"
      :loading="reviewLoading"
      @save-ratings="onSaveRatings"
      @get-adjectival-rating="onGetAdjectivalRating"
    />

    <IPCRView
      v-model="showView"
      :ipcr-data="selectedIPCR"
      :loading="viewLoading"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import IPCRList from '@/components/IPCR/IPCRList.vue'
import IPCRForm from '@/components/IPCR/IPCRForm.vue'
import EmployeeReview from '@/components/IPCR/EmployeeReview.vue'
import IPCRView from '@/components/IPCR/IPCRView.vue'
import { useIPCR } from '@/composable/useIPCR'
import { ElMessage, ElMessageBox } from 'element-plus'

const { 
  loading, 
  ipcrRatings, 
  formData,
  reviewData,
  fetchIPCRRatings,
  fetchFormData,
  saveIPCR,
  fetchReviewData,
  getAdjectivalRating,
  saveRatings,
  deleteIPCR
} = useIPCR()

// Component state
const showForm = ref(false)
const showReview = ref(false)
const showView = ref(false)
const selectedIPCR = ref(null)
const formLoading = ref(false)
const reviewLoading = ref(false)
const viewLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchIPCRRatings()
  } catch (error) {
    console.error('Failed to reload IPCR ratings:', error)
  }
}

const onAdd = async () => {
  try {
    formLoading.value = true
    selectedIPCR.value = null
    await fetchFormData(0)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onEdit = async (ipcr) => {
  try {
    formLoading.value = true
    selectedIPCR.value = ipcr
    await fetchFormData(ipcr.id)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onView = (ipcr) => {
  selectedIPCR.value = ipcr
  showView.value = true
}

const onReview = async (ipcr) => {
  try {
    reviewLoading.value = true
    await fetchReviewData(ipcr.id)
    showReview.value = true
  } catch (error) {
    console.error('Failed to load review data:', error)
    ElMessage.error('Failed to load employee review data')
  } finally {
    reviewLoading.value = false
  }
}

const onSave = async (ipcrData) => {
  try {
    await saveIPCR(ipcrData.id, ipcrData)
    showForm.value = false
    await reload()
  } catch (error) {
    console.error('Save failed:', error)
  }
}

const onSaveRatings = async (ratingsData) => {
  try {
    const ipcrId = reviewData.value.length > 0 ? reviewData.value[0].id : 0
    await saveRatings(ipcrId, ratingsData)
    showReview.value = false
    ElMessage.success('Employee ratings saved successfully')
  } catch (error) {
    console.error('Save ratings failed:', error)
  }
}

const onGetAdjectivalRating = async (numericalRating) => {
  try {
    return await getAdjectivalRating(numericalRating)
  } catch (error) {
    console.error('Failed to get adjectival rating:', error)
    return ''
  }
}

const onDelete = async (ipcr) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete this IPCR record? This action cannot be undone.`,
      'Confirm Delete',
      { type: 'warning' }
    )
    await deleteIPCR(ipcr.id)
    await reload()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('Delete failed:', error)
    }
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

