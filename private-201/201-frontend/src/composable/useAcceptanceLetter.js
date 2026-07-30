import { ref } from 'vue'
import { acceptanceLetterApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useAcceptanceLetter() {
    const loading = ref(false)
    const generateLoading = ref(false)
    const applicants = ref([])
    const signatories = ref([])
    const departments = ref([])

    const fetchApplicants = async () => {
        loading.value = true
        try {
            const res = await acceptanceLetterApi.getApplicants()
            applicants.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load applicants for acceptance letter')
            applicants.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchSignatories = async () => {
        loading.value = true
        try {
            const res = await acceptanceLetterApi.getSignatories()
            signatories.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load signatories for acceptance letter')
            signatories.value = []
        } finally {
            loading.value = false
        }
    }

    const fetchDepartments = async () => {
        loading.value = true
        try {
            const res = await acceptanceLetterApi.getDepartments()
            departments.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load departments for acceptance letter')
            departments.value = []
        } finally {
            loading.value = false
        }
    }

    const generateAcceptancePdf = async (form, returnBlob = false) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterApi.generatePDF(form)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            if (returnBlob) {
                return blob
            }
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance letter')
                } else {
                    ElMessage.error('Failed to generate acceptance letter')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance letter')
            }
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    const generateAcceptanceWord = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterApi.generateWord(form)
            const blob = new Blob([res.data], { type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' })
            return blob
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating acceptance letter Word')
                } else {
                    ElMessage.error('Failed to generate acceptance letter Word')
                }
            } catch {
                ElMessage.error('Failed to generate acceptance letter Word')
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

    const sendEmail = async (form) => {
        generateLoading.value = true
        try {
            const res = await acceptanceLetterApi.sendEmail(form)
            const data = res.data?.data || {}
            
            if (data.sent > 0) {
                ElMessage.success(res.data?.message || 'Acceptance letters sent successfully')
            }
            
            if (data.failed > 0 && data.errors && data.errors.length > 0) {
                data.errors.forEach(err => {
                    ElMessage.warning(err)
                })
            }
            
            return data
        } catch (e) {
            console.error(e)
            ElMessage.error(e.response?.data?.message || 'Failed to send acceptance letters')
            throw e
        } finally {
            generateLoading.value = false
        }
    }

    return {
        loading,
        generateLoading,
        applicants,
        signatories,
        departments,
        fetchApplicants,
        fetchSignatories,
        fetchDepartments,
        generateAcceptancePdf,
        generateAcceptanceWord,
        downloadPDFFromBlob,
        downloadWordFromBlob,
        sendEmail
    }
}
