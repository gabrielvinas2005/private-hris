<template>
  <PageScaffold
    title="OPCR"
    subtitle="Office Performance Commitment and Review"
  >
    <OPCRList
      :opcr-ratings="opcrRatings"
      :loading="loading"
      @refresh="reload"
      @add="onAdd"
      @view="onView"
      @edit="onEdit"
      @review="onReview"
      @delete="onDelete"
    />

    <OPCRForm
      v-model="showForm"
      :opcr-data="selectedOPCR"
      :form-data="formData"
      :loading="formLoading"
      @save="onSave"
    />

    <OfficeHeadReview
      v-model="showReview"
      :review-data="reviewData"
      :loading="reviewLoading"
      @save-ratings="onSaveRatings"
      @get-adjectival-rating="onGetAdjectivalRating"
    />

    <OPCRView
      v-model="showView"
      :opcr-data="selectedOPCR"
      :loading="viewLoading"
    />
  </PageScaffold>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PageScaffold from '@/components/PageScaffold.vue'
import OPCRList from '@/components/OPCR/OPCRList.vue'
import OPCRForm from '@/components/OPCR/OPCRForm.vue'
import OfficeHeadReview from '@/components/OPCR/OfficeHeadReview.vue'
import OPCRView from '@/components/OPCR/OPCRView.vue'
import { useOPCR } from '@/composable/useOPCR'
import { ElMessage, ElMessageBox } from 'element-plus'

const { 
  loading, 
  opcrRatings, 
  formData,
  reviewData,
  fetchOPCRRatings,
  fetchFormData,
  saveOPCR,
  fetchReviewData,
  getAdjectivalRating,
  saveRatings,
  deleteOPCR
} = useOPCR()

// Component state
const showForm = ref(false)
const showReview = ref(false)
const showView = ref(false)
const selectedOPCR = ref(null)
const formLoading = ref(false)
const reviewLoading = ref(false)
const viewLoading = ref(false)

// Methods
const reload = async () => {
  try {
    await fetchOPCRRatings()
  } catch (error) {
    console.error('Failed to reload OPCR ratings:', error)
  }
}

const onAdd = async () => {
  try {
    formLoading.value = true
    selectedOPCR.value = null
    await fetchFormData(0)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onEdit = async (opcr) => {
  try {
    formLoading.value = true
    selectedOPCR.value = opcr
    await fetchFormData(opcr.id)
    showForm.value = true
  } catch (error) {
    console.error('Failed to load form data:', error)
    ElMessage.error('Failed to load form data')
  } finally {
    formLoading.value = false
  }
}

const onView = (opcr) => {
  selectedOPCR.value = opcr
  showView.value = true
}

const onReview = async (opcr) => {
  try {
    reviewLoading.value = true
    await fetchReviewData(opcr.id)
    showReview.value = true
  } catch (error) {
    console.error('Failed to load review data:', error)
    ElMessage.error('Failed to load office head review data')
  } finally {
    reviewLoading.value = false
  }
}

const onSave = async (opcrData) => {
  try {
    await saveOPCR(opcrData.id, opcrData)
    showForm.value = false
    await reload()
  } catch (error) {
    console.error('Save failed:', error)
  }
}

const onSaveRatings = async (ratingsData) => {
  try {
    const opcrId = reviewData.value.length > 0 ? reviewData.value[0].id : 0
    await saveRatings(opcrId, ratingsData)
    showReview.value = false
    ElMessage.success('Office head ratings saved successfully')
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

const onDelete = async (opcr) => {
  try {
    await ElMessageBox.confirm(
      `Are you sure you want to delete this OPCR record? This action cannot be undone.`,
      'Confirm Delete',
      { type: 'warning' }
    )
    await deleteOPCR(opcr.id)
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

