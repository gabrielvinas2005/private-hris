import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { stepIncrementApi } from '@/services/api'

export function useStepIncrement() {
    const loading = ref(false)
    const stepIncrements = ref([])
    const employeesNoStep = ref([])
    const formData = ref(null)
    const stepIncrementDetail = ref(null)
    const employees = ref([])

    const fetchStepIncrements = async () => {
        try {
            loading.value = true
            const res = await stepIncrementApi.index()
            // The actual data is in res.data.data, not res.data
            stepIncrements.value = res.data.data || []
        } catch (e) {
            ElMessage.error('Failed to load step increments')
            console.error('Fetch error:', e)
            throw e
        } finally {
            loading.value = false
        }
    }

    const loadEmployees = async (monthId, yearId) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.loadEmployees(monthId, yearId)

            // Extract employee data from response
            const responseData = res.data?.data || res.data || {}
            const employeeList = responseData.employees || []

            employees.value = employeeList
            return {
                employees: employeeList,
                salary_steps: responseData.salary_steps || []
            }
        } catch (e) {
            ElMessage.error('Failed to load employees for step increment: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const loadStepIncrementData = async (monthId, yearId) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.loadStepIncrementData(monthId, yearId)

            // Extract step increment data from response
            const responseData = res.data?.data || res.data || {}
            const employeeList = responseData.employees || []

            employees.value = employeeList
            return {
                employees: employeeList,
                salary_steps: responseData.salary_steps || [],
                step_increment_data: responseData.step_increment_data || [],
                formData: responseData
            }
        } catch (e) {
            ElMessage.error('Failed to load step increment data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchForm = async () => {
        try {
            loading.value = true
            const res = await stepIncrementApi.add()
            formData.value = res.data.data || res.data || {}
            return formData.value
        } catch (e) {
            ElMessage.error('Failed to load step increment form data: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const saveStepIncrement = async (payload) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.store(payload)
            ElMessage.success(res.data.message || 'Step increment saved successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Save failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const forwardStepIncrement = async (monthId, yearId) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.forwardApprover(monthId, yearId)
            ElMessage.success(res.data.message || 'Step increment forwarded successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Forward failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const approveStepIncrement = async (monthId, yearId) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.approve(monthId, yearId)
            ElMessage.success(res.data.message || 'Step increment approved successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Approval failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const rejectStepIncrement = async (monthId, yearId, remarks) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.reject(monthId, yearId, remarks)
            ElMessage.success(res.data.message || 'Step increment rejected successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Rejection failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const showStepIncrement = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.show(id)
            stepIncrementDetail.value = res.data.data || null
            return stepIncrementDetail.value
        } catch (e) {
            ElMessage.error('Failed to fetch step increment details: ' + (e.response?.data?.message || e.message))
            throw e
        } finally {
            loading.value = false
        }
    }

    const processMultipleStepIncrements = async (payload) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.addStep(payload)
            ElMessage.success(res.data.message || 'Step increments processed successfully')
            return res.data.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Processing failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    const deleteStepIncrement = async (id) => {
        try {
            loading.value = true
            const res = await stepIncrementApi.delete(id)
            ElMessage.success(res.data.message || 'Step increment deleted successfully')
            return res.data
        } catch (e) {
            const msg = e.response?.data?.message || 'Delete failed'
            ElMessage.error(msg)
            throw e
        } finally {
            loading.value = false
        }
    }

    return {
        loading,
        stepIncrements,
        employeesNoStep,
        employees,
        formData,
        stepIncrementDetail,
        fetchStepIncrements,
        loadEmployees,
        loadStepIncrementData,
        fetchForm,
        saveStepIncrement,
        forwardStepIncrement,
        approveStepIncrement,
        rejectStepIncrement,
        showStepIncrement,
        processMultipleStepIncrements,
        deleteStepIncrement
    }
}
