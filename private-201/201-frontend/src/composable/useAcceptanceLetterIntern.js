import { ref } from 'vue'
import { acceptanceLetterInternApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useAcceptanceLetterIntern() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const saveLoading = ref(false)
    const records = ref([])

    const fetchRecords = async () => {
        loading.value = true
        try {
            const res = await acceptanceLetterInternApi.getAll()
            records.value = res.data?.data || []
            return records.value
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to fetch acceptance letter intern records'
            ElMessage.error(errorMessage)
            throw e
        } finally {
            loading.value = false
        }
    }

    const fetchStudents = async (id) => {
        try {
            const res = await acceptanceLetterInternApi.getStudents(id)
            return res.data?.data || { record: null, students: [] }
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to fetch students'
            ElMessage.error(errorMessage)
            throw e
        }
    }

    const updateStudentStatus = async (studentId, isActive) => {
        try {
            const res = await acceptanceLetterInternApi.updateStudentStatus(studentId, isActive)
            ElMessage.success(res.data?.message || 'Student status updated successfully')
            return res.data
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to update student status'
            ElMessage.error(errorMessage)
            throw e
        }
    }

    const saveAcceptanceLetter = async (form) => {
        saveLoading.value = true
        try {
            const res = await acceptanceLetterInternApi.store(form)
            ElMessage.success(res.data?.message || 'Acceptance letter intern record saved successfully')
            return res.data
        } catch (e) {
            const errorMessage = e?.response?.data?.message || e?.response?.data?.error || 'Failed to save acceptance letter intern record'
            ElMessage.error(errorMessage)
            throw e
        } finally {
            saveLoading.value = false
        }
    }

    const generateAcceptancePdf = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterInternApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance letter intern')
                } else {
                    ElMessage.error('Failed to generate acceptance letter intern')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance letter intern')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const generateAcceptanceWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterInternApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance letter intern Word document')
                } else {
                    ElMessage.error('Failed to generate acceptance letter intern Word document')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance letter intern Word document')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const previewAcceptanceWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterInternApi.previewWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance letter intern Word preview')
                } else {
                    ElMessage.error('Failed to generate acceptance letter intern Word preview')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance letter intern Word preview')
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

    const downloadWordFromBlob = (blob, filename) => {
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        URL.revokeObjectURL(url)
    }

    return {
        loading,
        generateLoading,
        saveLoading,
        records,
        fetchRecords,
        fetchStudents,
        updateStudentStatus,
        saveAcceptanceLetter,
        generateAcceptancePdf,
        generateAcceptanceWord,
        previewAcceptanceWord,
        downloadPDFFromBlob,
        downloadWordFromBlob
    }
}

