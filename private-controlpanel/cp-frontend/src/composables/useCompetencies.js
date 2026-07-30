import { ref, reactive } from 'vue'
import ApiService from '../Services/api.js'

export function useCompetencies() {
    const items = ref([])
    const loading = ref(false)
    const saving = ref(false)
    const formData = reactive({
        name: '',
        active: true,
        subcompetencies: []
    })

    // Fetch all competencies
    async function fetchList() {
        loading.value = true
        try {
            const response = await ApiService.getCompetencies()
            if (response && response.success) {
                items.value = response.data.map(item => ({
                    id: parseInt(item.id),
                    name: item.name || '',
                    active: item.active === true || item.active === 1 || item.active === '1'
                }))
            }
        } catch (error) {
            console.error('Error fetching competencies:', error)
        } finally {
            loading.value = false
        }
    }

    // Add new competency
    async function addCompetency(data = null) {
        saving.value = true
        try {
            const formDataToUse = data || formData
            const payload = {
                name: formDataToUse.name,
                active: formDataToUse.active,
                code: formDataToUse.subcompetencies.map(sub => sub.code || ''),
                subcompetenciesname: formDataToUse.subcompetencies.map(sub => sub.name || ''),
                description: formDataToUse.subcompetencies.map(sub => sub.description || ''),
                subcompetencies_id: formDataToUse.subcompetencies.map(sub => sub.id || null)
            }
            const response = await ApiService.saveCompetency(payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error adding competency:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Update existing competency
    async function updateCompetency(id, data = null) {
        saving.value = true
        try {
            const formDataToUse = data || formData
            const payload = {
                name: formDataToUse.name,
                active: formDataToUse.active,
                code: formDataToUse.subcompetencies.map(sub => sub.code || ''),
                subcompetenciesname: formDataToUse.subcompetencies.map(sub => sub.name || ''),
                description: formDataToUse.subcompetencies.map(sub => sub.description || ''),
                subcompetencies_id: formDataToUse.subcompetencies.map(sub => sub.id || null)
            }
            const response = await ApiService.updateCompetency(id, payload)
            if (response && response.success) {
                await fetchList() // Refresh the list
                resetForm()
                return response
            }
        } catch (error) {
            console.error('Error updating competency:', error)
            throw error
        } finally {
            saving.value = false
        }
    }

    // Delete competency (subcompetency)
    async function deleteCompetency(typeId, id) {
        try {
            const response = await ApiService.deleteCompetency(typeId, id)
            if (response && response.success) {
                await fetchList() // Refresh the list
                return response
            }
        } catch (error) {
            console.error('Error deleting competency:', error)
            throw error
        }
    }

    // Get competency for editing
    async function getCompetencyForEdit(id) {
        try {
            const response = await ApiService.getCompetencyForEdit(id)
            if (response && response.success) {
                const data = response.data.competencies
                formData.name = data.name || ''
                formData.active = data.active === true || data.active === 1 || data.active === '1'
                formData.subcompetencies = response.data.subcompetencies.map(sub => ({
                    id: sub.id,
                    code: sub.code || '',
                    name: sub.name || '',
                    description: sub.description || ''
                }))
                return response
            }
        } catch (error) {
            console.error('Error fetching competency for edit:', error)
            throw error
        }
    }

    // Get form data for adding
    async function getFormData() {
        try {
            const response = await ApiService.getCompetencyFormData()
            if (response && response.success) {
                return response.data
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            throw error
        }
    }

    // Add subcompetency row
    function addSubcompetencyRow() {
        formData.subcompetencies.push({
            id: null,
            code: '',
            name: '',
            description: ''
        })
    }

    // Remove subcompetency row
    function removeSubcompetencyRow(index) {
        formData.subcompetencies.splice(index, 1)
    }

    // Delete subcompetency from database
    async function deleteSubcompetency(subcompetencyId) {
        try {
            const response = await ApiService.deleteCompetency(1, subcompetencyId)
            if (response && response.success) {
                return response
            }
        } catch (error) {
            console.error('Error deleting subcompetency:', error)
            throw error
        }
    }

    // Reset form data
    function resetForm() {
        formData.name = ''
        formData.active = true
        formData.subcompetencies = []
    }

    // Set form data for editing
    function setFormData(data) {
        formData.name = data.name || ''
        formData.active = data.active === true || data.active === 1 || data.active === '1'
        formData.subcompetencies = data.subcompetencies || []
    }

    return {
        items,
        loading,
        saving,
        formData,
        fetchList,
        addCompetency,
        updateCompetency,
        deleteCompetency,
        getCompetencyForEdit,
        getFormData,
        addSubcompetencyRow,
        removeSubcompetencyRow,
        deleteSubcompetency,
        resetForm,
        setFormData
    }
}
