import { ref, reactive, computed } from 'vue'
import { ElMessage } from 'element-plus'
import ApiService from '../services/api.js'

export function usePassSlip() {
  // Utility function to get current user ID from localStorage
  const getCurrentUserId = () => {
    const raw = localStorage.getItem('user_data')
    try { return raw ? JSON.parse(raw).id : null } catch { return null }
  }

  const resolvePassSlipId = (passSlipOrId) => {
    if (passSlipOrId === null || typeof passSlipOrId === 'undefined') return null
    if (typeof passSlipOrId === 'object') return passSlipOrId.id ?? null
    return passSlipOrId
  }
  const state = reactive({
    passSlips: [],
    passSlipsForApproval: [],
    isApprover: false,
    employeeId: null,
    loading: false,
    allowed: true
  })

  const uiState = reactive({
    showFormModal: false,
    showApproveModal: false,
    showDisapproveModal: false,
    selectedPassSlip: null,
    previewUrl: null,
    showPrintModal: false
  })

  // Filter states
  const search = ref('')
  const statusFilter = ref('')
  const activeApproverTab = ref('pending')

  // Fetch pass slips data
  const fetchPassSlips = async () => {
    try {
      state.loading = true
      const userId = getCurrentUserId()
      if (!userId) {
        throw new Error('No authenticated user')
      }

      const response = await ApiService.request(`/pass-slips/${userId}`, {
        method: 'GET'
      })
      
      if (response.success) {
        state.passSlips = response.data.pass_slips || []
        state.passSlipsForApproval = response.data.pass_slips_for_approval || []
        state.isApprover = response.data.is_approver || false
        state.employeeId = response.data.employee_id
      }
    } catch (error) {
      console.error('Error fetching pass slips:', error)
      ElMessage.error('Failed to load pass slips')
    } finally {
      state.loading = false
    }
  }

  // Create or update pass slip
  const savePassSlip = async (passSlipData) => {
    try {
      let response
      if (passSlipData.id) {
        // Update existing
        response = await ApiService.request(`/pass-slips/${passSlipData.id}`, {
          method: 'PUT',
          body: JSON.stringify(passSlipData)
        })
      } else {
        // Create new
        response = await ApiService.request('/pass-slips', {
          method: 'POST',
          body: JSON.stringify({
            ...passSlipData,
            employee_id: state.employeeId
          })
        })
      }

      if (response.success) {
        ElMessage.success(passSlipData.id ? 'Pass slip updated successfully' : 'Pass slip created successfully')
        await fetchPassSlips()
        return true
      }
      return false
    } catch (error) {
      console.error('Error saving pass slip:', error)
      ElMessage.error(error.message || 'Failed to save pass slip')
      return false
    }
  }

  // Delete pass slip (accepts numeric id or full row from PassSlipTable)
  const deletePassSlip = async (passSlipOrId) => {
    const id = resolvePassSlipId(passSlipOrId)
    if (!id) {
      ElMessage.error('Unable to identify pass slip to delete.')
      return
    }

    try {
      const response = await ApiService.request(`/pass-slips/${id}`, {
        method: 'DELETE'
      })
      if (response.success) {
        ElMessage.success('Pass slip deleted successfully')
        await fetchPassSlips()
      } else {
        ElMessage.error(response?.message || 'Failed to delete pass slip')
      }
    } catch (error) {
      console.error('Error deleting pass slip:', error)
      ElMessage.error(error?.message || 'Failed to delete pass slip')
    }
  }

  // Approve pass slip
  const approvePassSlip = async (id, divisionChief, remarks = '') => {
    try {
      const response = await ApiService.request(`/pass-slips/${id}/status`, {
        method: 'POST',
        body: JSON.stringify({
          status: 'approved',
          division_chief: divisionChief,
          remarks: remarks
        })
      })

      if (response.success) {
        ElMessage.success('Pass slip approved successfully')
        await fetchPassSlips()
        return true
      }
      return false
    } catch (error) {
      console.error('Error approving pass slip:', error)
      ElMessage.error('Failed to approve pass slip')
      return false
    }
  }

  // Disapprove pass slip
  const disapprovePassSlip = async (id, remarks) => {
    try {
      const response = await ApiService.request(`/pass-slips/${id}/status`, {
        method: 'POST',
        body: JSON.stringify({
          status: 'disapproved',
          remarks: remarks
        })
      })

      if (response.success) {
        ElMessage.success('Pass slip disapproved')
        await fetchPassSlips()
        return true
      }
      return false
    } catch (error) {
      console.error('Error disapproving pass slip:', error)
      ElMessage.error('Failed to disapprove pass slip')
      return false
    }
  }

  // Print pass slip — fetch PDF via API (avoids hardcoded localhost URLs on other devices)
  const printPassSlip = async (passSlip) => {
    const id = typeof passSlip === 'object' ? passSlip.id : passSlip
    if (!id) return

    if (uiState.previewUrl && uiState.previewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(uiState.previewUrl)
    }

    uiState.previewUrl = null
    uiState.showPrintModal = true

    try {
      ApiService.refreshBaseUrl()
      const blob = await ApiService.request(`/pass-slips/${id}/pdf`, {
        responseType: 'blob',
        headers: { Accept: 'application/pdf,*/*' }
      })

      if (!(blob instanceof Blob) || blob.size === 0) {
        throw new Error('Empty PDF response')
      }

      uiState.previewUrl = URL.createObjectURL(blob)
    } catch (error) {
      console.error('Error loading pass slip PDF:', error)
      uiState.showPrintModal = false
      ElMessage.error('Failed to load pass slip PDF. Check that the API URL is reachable from this device.')
    }
  }

  // Filtered pass slips
  const filteredPassSlips = computed(() => {
    let filtered = state.passSlips

    if (search.value) {
      const searchLower = search.value.toLowerCase()
      filtered = filtered.filter(ps => 
        ps.destination?.toLowerCase().includes(searchLower) ||
        ps.purpose?.toLowerCase().includes(searchLower)
      )
    }

    if (statusFilter.value) {
      filtered = filtered.filter(ps => ps.status === statusFilter.value)
    }

    return filtered
  })

  // Filtered pass slips for approval based on active tab
  const filteredPassSlipsForApproval = computed(() => {
    return state.passSlipsForApproval.filter(ps => {
      if (activeApproverTab.value === 'pending') {
        return ps.status === 'pending'
      } else if (activeApproverTab.value === 'approved') {
        return ps.status === 'approved'
      } else if (activeApproverTab.value === 'disapproved') {
        return ps.status === 'disapproved'
      }
      return false
    })
  })

  // UI Actions
  const openFormModal = (passSlip = null) => {
    uiState.selectedPassSlip = passSlip
    uiState.showFormModal = true
  }

  const closeFormModal = () => {
    uiState.showFormModal = false
    uiState.selectedPassSlip = null
  }

  const openApproveModal = (passSlip) => {
    uiState.selectedPassSlip = passSlip
    uiState.showApproveModal = true
  }

  const closeApproveModal = () => {
    uiState.showApproveModal = false
    uiState.selectedPassSlip = null
  }

  const openDisapproveModal = (passSlip) => {
    uiState.selectedPassSlip = passSlip
    uiState.showDisapproveModal = true
  }

  const closeDisapproveModal = () => {
    uiState.showDisapproveModal = false
    uiState.selectedPassSlip = null
  }

  const closePrintModal = () => {
    uiState.showPrintModal = false
    if (uiState.previewUrl && uiState.previewUrl.startsWith('blob:')) {
      URL.revokeObjectURL(uiState.previewUrl)
    }
    uiState.previewUrl = null
  }

  return {
    state,
    uiState,
    search,
    statusFilter,
    activeApproverTab,
    filteredPassSlips,
    filteredPassSlipsForApproval,
    fetchPassSlips,
    savePassSlip,
    deletePassSlip,
    approvePassSlip,
    disapprovePassSlip,
    printPassSlip,
    openFormModal,
    closeFormModal,
    openApproveModal,
    closeApproveModal,
    openDisapproveModal,
    closeDisapproveModal,
    closePrintModal
  }
}
