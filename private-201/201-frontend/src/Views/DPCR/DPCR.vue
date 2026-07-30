<template>
  <PageScaffold
    title="DPCR"
    subtitle="Department Performance Commitment and Review"
  >
    <DPCRList
      :dpcr-ratings="dpcrRatings"
      :loading="loading"
      @refresh="reload"
      @add="onAdd"
      @view="onView"
      @edit="onEdit"
      @review="onReview"
      @delete="onDelete"
    />

    <DPCRForm
      v-model="showForm"
      :dpcr-data="selectedDPCR"
      :form-data="formData"
      :loading="formLoading"
      @save="onSave"
    />

    <DepartmentHeadReview
      v-model="showReview"
      :review-data="reviewData"
      :loading="reviewLoading"
      @save-ratings="onSaveRatings"
    />

    <DPCRView
      v-model="showView"
      :dpcr-data="selectedDPCR"
      :loading="viewLoading"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import DPCRList from '@/components/DPCR/DPCRList.vue'
import DPCRForm from '@/components/DPCR/DPCRForm.vue'
import DepartmentHeadReview from '@/components/DPCR/DepartmentHeadReview.vue'
import DPCRView from '@/components/DPCR/DPCRView.vue'
import { useDPCR } from '@/composable/useDPCR'
import { ElMessage, ElMessageBox } from 'element-plus'

const { 
  loading, 
  dpcrRatings, 
  formData,
  reviewData,
  fetchDPCRRatings,
  fetchFormData,
  saveDPCR,
  fetchReviewData,
  saveRating,
  deleteDPCR
} = useDPCR()

// Component state
const showForm = ref(false)
const showReview = ref(false)
const showView = ref(false)
const selectedDPCR = ref(null)
const formLoading = ref(false)
const reviewLoading = ref(false)
const viewLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchDPCRRatings()
  } catch (error) {
    console.error('Failed to reload DPCR ratings:', error)
  }
}

const onAdd = async () => {
  try {
    formLoading.value = true
    selectedDPCR.value = null
    await fetchFormData(0)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onEdit = async (dpcr) => {
  try {
    formLoading.value = true
    selectedDPCR.value = dpcr
    await fetchFormData(dpcr.id)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onView = (dpcr) => {
  selectedDPCR.value = dpcr
  showView.value = true
}

const onReview = async (dpcr) => {
  try {
    reviewLoading.value = true
    await fetchReviewData(dpcr.id)
    showReview.value = true
  } catch (error) {
    console.error('Failed to load review data:', error)
    ElMessage.error('Failed to load department head review data')
  } finally {
    reviewLoading.value = false
  }
}

const onSaveRatings = async (ratingsData) => {
  try {
    const dpcrId = reviewData.value.length > 0 ? reviewData.value[0].dpcr_header_id : 0
    await saveRating(dpcrId, ratingsData)
    showReview.value = false
    ElMessage.success('Department head ratings saved successfully')
  } catch (error) {
    console.error('Save ratings failed:', error)
  }
}

const onSave = async (dpcrData) => {
  try {
    await saveDPCR(dpcrData.id, dpcrData)
    showForm.value = false
    await reload()
  } catch (error) {
    console.error('Save failed:', error)
  }
}

const onDelete = async (dpcr) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete this DPCR record? This action cannot be undone.`,
      'Confirm Delete',
      { type: 'warning' }
    )
    await deleteDPCR(dpcr.id)
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
