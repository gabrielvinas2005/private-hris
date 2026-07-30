import { ref, computed } from 'vue'
import { plantillaReportApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function usePlantillaReport() {
    // State
    const loading = ref(false)
    const generateLoading = ref(false)
    const plantillaData = ref([])

    // Computed
    const totalPositions = computed(() => {
        return Array.isArray(plantillaData.value) ? plantillaData.value.length : 0
    })

    const vacantPositions = computed(() => {
        return Array.isArray(plantillaData.value)
            ? plantillaData.value.filter(item => item.status === 'Vacant').length
            : 0
    })

    const occupiedPositions = computed(() => {
        return Array.isArray(plantillaData.value)
            ? plantillaData.value.filter(item => item.status === 'Occupied').length
            : 0
    })

    // Helper function to check if plantilla is active
    const isActive = (active) => {
        if (active === null || active === undefined) return false
        // Handle boolean, integer, and string values
        return active === true || active === 1 || active === '1' || String(active).toLowerCase() === 'true'
    }

    const activePositions = computed(() => {
        return Array.isArray(plantillaData.value)
            ? plantillaData.value.filter(item => isActive(item.active)).length
            : 0
    })

    const inactivePositions = computed(() => {
        return Array.isArray(plantillaData.value)
            ? plantillaData.value.filter(item => !isActive(item.active)).length
            : 0
    })

    // Methods
    const fetchPlantillaData = async () => {
        loading.value = true
        try {
            console.log('Fetching plantilla data...')
            const response = await plantillaReportApi.getPlantillaData()
            console.log('Plantilla Report API response:', response)
            console.log('Response data structure:', response.data)

            if (response.data && response.data.data) {
                plantillaData.value = Array.isArray(response.data.data) ? response.data.data : []
                console.log('Plantilla data loaded:', plantillaData.value.length, plantillaData.value)
            } else if (response.data && Array.isArray(response.data)) {
                // Handle case where data is directly in response.data
                plantillaData.value = response.data
                console.log('Plantilla data loaded (direct):', plantillaData.value.length, plantillaData.value)
            } else {
                plantillaData.value = []
                console.warn('No plantilla data found in response:', response.data)
            }
        } catch (error) {
            console.error('Failed to fetch plantilla data:', error)
            ElMessage.error('Failed to load plantilla data')
            plantillaData.value = []
        } finally {
            loading.value = false
        }
    }

    const generatePlantillaPDF = async (statusId) => {
        generateLoading.value = true
        try {
            const response = await plantillaReportApi.generatePDF(statusId)

            // Create blob URL for preview
            const blob = new Blob([response.data], { type: 'application/pdf' })
            const url = URL.createObjectURL(blob)

            return url
        } catch (error) {
            console.error('Failed to generate Plantilla Report PDF:', error)
            ElMessage.error('Failed to generate Plantilla Report PDF')
            throw error
        } finally {
            generateLoading.value = false
        }
    }

    const downloadPDFFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = filename
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        URL.revokeObjectURL(url)
    }

    // Filter methods
    const getFilteredData = (statusFilter = 'all', activeFilter = 'all') => {
        if (!Array.isArray(plantillaData.value)) return []

        let filtered = [...plantillaData.value]

        // Filter by status
        if (statusFilter === 'vacant') {
            filtered = filtered.filter(item => item.status === 'Vacant')
        } else if (statusFilter === 'occupied') {
            filtered = filtered.filter(item => item.status === 'Occupied')
        }

        // Filter by active status
        if (activeFilter === 'active') {
            filtered = filtered.filter(item => isActive(item.active))
        } else if (activeFilter === 'inactive') {
            filtered = filtered.filter(item => !isActive(item.active))
        }

        return filtered
    }

    return {
        // State
        loading,
        generateLoading,
        plantillaData,

        // Computed
        totalPositions,
        vacantPositions,
        occupiedPositions,
        activePositions,
        inactivePositions,

        // Methods
        fetchPlantillaData,
        generatePlantillaPDF,
        downloadPDFFromBlob,
        getFilteredData
    }
}
