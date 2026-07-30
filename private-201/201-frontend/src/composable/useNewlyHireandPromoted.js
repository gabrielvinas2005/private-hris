import { ref } from 'vue'
import { newlyHiredAndPromotedApi } from '@/services/api'
import { ElMessage } from 'element-plus'

/**
 * Composable for fetching Newly Hired and Promoted records
 */
export function useNewlyHireandPromoted() {
  const loading = ref(false)
  const newlyHiredAndPromotedRecords = ref([])

  const fetchNewlyHiredAndPromotedRecords = async () => {
    try {
      loading.value = true
      const res = await newlyHiredAndPromotedApi.index()
      // API is using ApiResponse trait → { success, message, data }
      newlyHiredAndPromotedRecords.value = res.data?.data || []
    } catch (e) {
      ElMessage.error(
        'Failed to load newly hired and promoted records: ' +
          (e.response?.data?.message || e.message)
      )
      console.error('Fetch error (NewlyHiredAndPromoted):', e)
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    loading,
    newlyHiredAndPromotedRecords,
    fetchNewlyHiredAndPromotedRecords,
  }
}