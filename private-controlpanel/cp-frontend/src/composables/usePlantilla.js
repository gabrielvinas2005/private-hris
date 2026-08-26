import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function usePlantilla() {
    // State
    const plantillas = ref([])
    const recentPlantillas = ref([])
    const loading = ref(false)
    const recentLoading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalPlantillas = computed(() => plantillas.value.length)

    // Methods
    async function fetchPlantillas() {
        try {
            loading.value = true
            const response = await apiService.getPlantillaSetup()

            if (response.success) {
                // Process the data to ensure proper types
                plantillas.value = response.data.map(plantilla => ({
                    ...plantilla,
                    id: parseInt(plantilla.id),
                    active: plantilla.active === "1" || plantilla.active === 1 || plantilla.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch plantillas')
            }
        } catch (error) {
            console.error('Error fetching plantillas:', error)
            ElMessage.error('Failed to fetch plantillas')
        } finally {
            loading.value = false
        }
    }

    async function fetchRecentPlantillas(limit = 5) {
        try {
            recentLoading.value = true
            const response = await apiService.getRecentPlantillas(limit)

            if (response.success) {
                recentPlantillas.value = response.data.map(plantilla => ({
                    ...plantilla,
                    id: parseInt(plantilla.id),
                    active: plantilla.active === "1" || plantilla.active === 1 || plantilla.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch recent plantillas')
            }
        } catch (error) {
            console.error('Error fetching recent plantillas:', error)
            ElMessage.error('Failed to fetch recent plantillas')
        } finally {
            recentLoading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getPlantillaFormData()

            if (response.success) {
                return {
                    success: true,
                    data: response.data
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch form data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            ElMessage.error('Failed to fetch form data')
            return { success: false }
        }
    }

    async function savePlantilla(plantillaData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const formatDate = (value) => {
                if (!value) return ''
                // Accept both Date objects and strings
                const d = value instanceof Date ? value : new Date(value)
                if (isNaN(d.getTime())) return ''
                const yyyy = d.getFullYear()
                const mm = String(d.getMonth() + 1).padStart(2, '0')
                const dd = String(d.getDate()).padStart(2, '0')
                return `${yyyy}-${mm}-${dd}`
            }

            const toInt = (v) => v === null || v === undefined || v === '' ? null : parseInt(v)

            const submitData = {
                code: plantillaData.code,
                position_id: toInt(plantillaData.position_id),
                salary_step_id: toInt(plantillaData.salary_step_id),
                salary_grade_id: toInt(plantillaData.salary_grade_id),
                department_id: toInt(plantillaData.department_id),
                employment_type_id: toInt(plantillaData.employment_type_id),
                unit: plantillaData.unit || '',
                publication_from: formatDate(plantillaData.publication_from),
                publication_to: formatDate(plantillaData.publication_to),
                status: plantillaData.status || '',
                active: plantillaData.active !== false,
                // Additional arrays for related data
                remark: (plantillaData.remark || []).map(r => r ?? '').filter(r => r !== ''),
                program: (plantillaData.program || []).map(p => p ?? '').filter(p => p !== ''),
                academic_level_id: (plantillaData.academic_level_id || []).map(toInt).filter(v => v !== null && !Number.isNaN(v)),
                education_id: (plantillaData.education_id || []).map(toInt),
                position: (plantillaData.position || []).map(p => p ?? '').filter(p => p !== ''),
                years: (plantillaData.years || []).map(y => y ?? 0),
                employment_record_id: (plantillaData.employment_record_id || []).map(toInt),
                eligibility_id: (plantillaData.eligibility_id || []).map(toInt).filter(v => v !== null && !Number.isNaN(v)),
                examination_id: (plantillaData.examination_id || []).map(toInt),
                training: (plantillaData.training || []).map(t => t ?? '').filter(t => t !== ''),
                hours: (plantillaData.hours || []).map(h => h ?? 0),
                training_id: (plantillaData.training_id || []).map(toInt),
                subcomp_id: plantillaData.subcomp_id || [],
                subcompetency_id: plantillaData.subcompetency_id || [],
                level: plantillaData.level || []
            }

            let response
            // Determine if this is an edit by checking for a scalar numeric ID (avoid array 'id' from child rows)
            const plantillaId = parseInt(plantillaData.id)
            const isEdit = !Number.isNaN(plantillaId)
            if (isEdit) {
                // Update existing plantilla
                response = await apiService.updatePlantillaSetup(plantillaId, submitData)
            } else {
                // Create new plantilla
                response = await apiService.savePlantillaSetup(submitData)
            }

            if (response && response.success) {
                ElMessage.success(response.message || 'Plantilla saved successfully')
                await fetchPlantillas() // Refresh the list
                return { success: true, data: response.data }
            } else {
                // Try to surface backend validation messages (Laravel format)
                const message = response?.message || 'Failed to save plantilla'
                const errors = response?.errors || null
                if (errors) {
                    // Show the first validation error
                    const firstField = Object.keys(errors)[0]
                    const firstMsg = errors[firstField]?.[0]
                    ElMessage.error(firstMsg || message)
                } else {
                    ElMessage.error(message)
                }
                return { success: false, errors }
            }
        } catch (error) {
            console.error('Error saving plantilla:', error)
            ElMessage.error('Failed to save plantilla')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deletePlantilla(plantillaId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this plantilla? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deletePlantilla(plantillaId)

            if (response.success) {
                ElMessage.success(response.message || 'Plantilla deleted successfully')
                await fetchPlantillas() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete plantilla')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting plantilla:', error)
            ElMessage.error('Failed to delete plantilla')
            return { success: false, error: error.message }
        }
    }

    async function getPlantillaForEdit(plantillaId) {
        try {
            const response = await apiService.getPlantillaForEdit(plantillaId)

            if (response.success) {
                return {
                    success: true,
                    data: response.data
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch plantilla data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching plantilla for edit:', error)
            ElMessage.error('Failed to fetch plantilla data')
            return { success: false, error: error.message }
        }
    }

    async function checkCode(code, excludeId = null) {
        try {
            const response = await apiService.checkPlantillaCode(code, excludeId)

            if (response.success) {
                return {
                    success: true,
                    exists: response.data.exists,
                    message: response.data.message
                }
            } else {
                return { success: false, message: 'Failed to check code' }
            }
        } catch (error) {
            console.error('Error checking code:', error)
            return { success: false, message: 'Failed to check code' }
        }
    }

    // Filter plantillas by search term and status
    function filterPlantillas(plantillas, searchTerm = '', statusFilter = '', departmentFilter = '') {
        return plantillas.filter(plantilla => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    plantilla.code.toLowerCase().includes(searchLower) ||
                    plantilla.position.toLowerCase().includes(searchLower) ||
                    (plantilla.department && plantilla.department.toLowerCase().includes(searchLower))

                if (!matchesSearch) return false
            }

            // Status filter
            if (statusFilter) {
                if (statusFilter === 'active' && !plantilla.active) return false
                if (statusFilter === 'inactive' && plantilla.active) return false
                if (statusFilter === 'vacant' && plantilla.status !== 'Vacant') return false
                if (statusFilter === 'occupied' && plantilla.status !== 'Occupied') return false
            }

            // Department filter
            if (departmentFilter) {
                if (plantilla.department !== departmentFilter) return false
            }

            return true
        })
    }

    return {
        // State
        plantillas,
        recentPlantillas,
        loading,
        recentLoading,
        formLoading,

        // Computed
        totalPlantillas,

        // Methods
        fetchPlantillas,
        fetchRecentPlantillas,
        fetchFormData,
        savePlantilla,
        deletePlantilla,
        getPlantillaForEdit,
        checkCode,
        filterPlantillas
    }
}
