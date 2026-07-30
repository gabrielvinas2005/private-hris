import { ref } from 'vue'
import { serviceRecordApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useServiceRecordCertificate() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const employees = ref([])

    const fetchEmployees = async () => {
        loading.value = true
        try {
            const res = await serviceRecordApi.getEmployees()
            employees.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load employees for service record')
            employees.value = []
        } finally {
            loading.value = false
        }
    }

    const generateServiceRecord = async (form) => {
        generateLoading.value = true
        try {
            const res = await serviceRecordApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating service record')
                } else {
                    ElMessage.error('Failed to generate service record')
                }
            } catch {
                ElMessage.error('Failed to generate service record')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadExcel = async (form) => {
        generateLoading.value = true
        try {
            const res = await serviceRecordApi.generateExcel(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating service record Excel')
                } else {
                    ElMessage.error('Failed to generate service record Excel')
                }
            } catch {
                ElMessage.error('Failed to generate service record Excel')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const downloadPDFFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    return { loading, generateLoading, employees, fetchEmployees, generateServiceRecord, downloadExcel, downloadPDFFromBlob }
}


