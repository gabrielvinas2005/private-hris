import { ref } from 'vue'
import { ElMessage, ElNotification } from 'element-plus'
import { stepIncrementApprovalApi } from '@/services/api'

export function useStepIncrementApproval() {
    const loading = ref(false)
    const stepIncrements = ref([])
    const approvalDetail = ref(null)

    const fetchPendingApprovals = async () => {
        try {
            loading.value = true
            const res = await stepIncrementApprovalApi.getPendingApprovals()
            
            // Endpoint already returns forwarded & pending items
            const allStepIncrements = res.data?.data || res.data || []
            stepIncrements.value = allStepIncrements

            // Show success notification with count
            ElNotification({
                title: 'Success',
                message: `Loaded ${allStepIncrements.length} pending approval(s)`,
                type: 'success',
                duration: 2000
            })

            return stepIncrements.value
        } catch (e) {
            const errorMsg = e.response?.data?.message || e.message || 'Unknown error'
            
            ElNotification({
                title: 'Failed to Load Approvals',
                message: errorMsg,
                type: 'error',
                duration: 5000
            })
            
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchApprovalDetails = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApprovalApi.getApprovalDetails(id)
            approvalDetail.value = res.data.data || res.data || null
            return approvalDetail.value
        } catch (e) {
            ElMessage.error('Failed to load approval details: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const forwardStepIncrement = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApprovalApi.forward(id)
            ElMessage.success(res.data.message || 'Step increment forwarded successfully')
            await fetchPendingApprovals() // Refresh the list
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Forward failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const approveStepIncrement = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApprovalApi.approve(id)
            
            ElNotification({
                title: 'Approved',
                message: res.data.message || 'Step increment approved successfully',
                type: 'success',
                duration: 3000
            })
            
            await fetchPendingApprovals() // Refresh the list
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Approval failed'
            
            ElNotification({
                title: 'Approval Failed',
                message: msg,
                type: 'error',
                duration: 5000
            })
            
            throw e
        } finally {
            loading.value = false
        }
    }

    const rejectStepIncrement = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApprovalApi.reject(id)
            
            ElNotification({
                title: 'Rejected',
                message: res.data.message || 'Step increment rejected successfully',
                type: 'warning',
                duration: 3000
            })
            
            await fetchPendingApprovals() // Refresh the list
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Rejection failed'
            
            ElNotification({
                title: 'Rejection Failed',
                message: msg,
                type: 'error',
                duration: 5000
            })
            
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        stepIncrements,
        approvalDetail,
        fetchPendingApprovals,
        fetchApprovalDetails,
        forwardStepIncrement,
        approveStepIncrement,
        rejectStepIncrement
    }
}
