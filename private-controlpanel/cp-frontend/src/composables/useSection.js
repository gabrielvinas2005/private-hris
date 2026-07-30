import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import apiService from '../Services/api.js'

export function useSection() {
    // State
    const sections = ref([])
    const employees = ref([])
    const divisions = ref([])
    const loading = ref(false)
    const formLoading = ref(false)

    // Computed
    const totalSections = computed(() => sections.value.length)

    // Methods
    async function fetchSections() {
        try {
            loading.value = true
            const response = await apiService.getSectionSetup()

            if (response.success) {
                // Process the data to ensure proper types
                sections.value = response.data.map(section => ({
                    ...section,
                    id: parseInt(section.id),
                    division_id: parseInt(section.division_id),
                    section_chief_id: section.section_chief_id ? parseInt(section.section_chief_id) : null,
                    active: section.active === "1" || section.active === 1 || section.active === true
                }))
            } else {
                ElMessage.error(response.message || 'Failed to fetch sections')
            }
        } catch (error) {
            console.error('Error fetching sections:', error)
            ElMessage.error('Failed to fetch sections')
        } finally {
            loading.value = false
        }
    }

    async function fetchFormData() {
        try {
            const response = await apiService.getSectionFormData()

            if (response.success) {
                employees.value = response.data.employees || []
                divisions.value = response.data.divisions || []
            } else {
                ElMessage.error(response.message || 'Failed to fetch form data')
            }
        } catch (error) {
            console.error('Error fetching form data:', error)
            ElMessage.error('Failed to fetch form data')
        }
    }

    async function saveSection(sectionData) {
        try {
            formLoading.value = true

            // Prepare data for submission
            const chiefId =
                sectionData.section_chief_id != null &&
                sectionData.section_chief_id !== '' &&
                Number(sectionData.section_chief_id) !== 0
                    ? parseInt(sectionData.section_chief_id, 10)
                    : null

            const submitData = {
                name: sectionData.name,
                division_id: parseInt(sectionData.division_id, 10),
                section_chief_id: chiefId
            }
            // Backend checks has('active'), so include only when true
            if (sectionData.active === true) {
                submitData.active = true
            }

            let response
            if (sectionData.id) {
                // Update existing section
                response = await apiService.updateSectionSetup(sectionData.id, submitData)
            } else {
                // Create new section
                response = await apiService.saveSectionSetup(submitData)
            }

            if (response.success) {
                ElMessage.success(response.message || 'Section saved successfully')
                await fetchSections() // Refresh the list
                return { success: true, data: response.data }
            } else {
                ElMessage.error(response.message || 'Failed to save section')
                return { success: false, errors: response.errors }
            }
        } catch (error) {
            console.error('Error saving section:', error)
            ElMessage.error('Failed to save section')
            return { success: false, error: error.message }
        } finally {
            formLoading.value = false
        }
    }

    async function deleteSection(sectionId) {
        try {
            await ElMessageBox.confirm(
                'Are you sure you want to delete this section? This action cannot be undone.',
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            )

            const response = await apiService.deleteSection(sectionId)

            if (response.success) {
                ElMessage.success(response.message || 'Section deleted successfully')
                await fetchSections() // Refresh the list
                return { success: true }
            } else {
                ElMessage.error(response.message || 'Failed to delete section')
                return { success: false }
            }
        } catch (error) {
            if (error === 'cancel') {
                return { success: false, cancelled: true }
            }
            console.error('Error deleting section:', error)
            ElMessage.error('Failed to delete section')
            return { success: false, error: error.message }
        }
    }

    async function getSectionForEdit(sectionId) {
        try {
            const response = await apiService.getSectionForEdit(sectionId)

            if (response.success) {
                return {
                    success: true,
                    section: response.data.section,
                    employees: response.data.employees || [],
                    divisions: response.data.divisions || []
                }
            } else {
                ElMessage.error(response.message || 'Failed to fetch section data')
                return { success: false }
            }
        } catch (error) {
            console.error('Error fetching section for edit:', error)
            ElMessage.error('Failed to fetch section data')
            return { success: false, error: error.message }
        }
    }

    // Filter sections by search term and type
    function filterSections(sections, searchTerm = '', typeFilter = '') {
        return sections.filter(section => {
            // Search filter
            if (searchTerm) {
                const searchLower = searchTerm.toLowerCase()
                const matchesSearch =
                    section.name.toLowerCase().includes(searchLower) ||
                    section.code?.toLowerCase().includes(searchLower) ||
                    section.supervisor?.toLowerCase().includes(searchLower) ||
                    section.division?.toLowerCase().includes(searchLower)

                if (!matchesSearch) return false
            }

            // Type filter
            if (typeFilter) {
                if (typeFilter === 'active' && !section.active) return false
                if (typeFilter === 'inactive' && section.active) return false
            }

            return true
        })
    }

    return {
        // State
        sections,
        employees,
        divisions,
        loading,
        formLoading,

        // Computed
        totalSections,

        // Methods
        fetchSections,
        fetchFormData,
        saveSection,
        deleteSection,
        getSectionForEdit,
        filterSections
    }
}
