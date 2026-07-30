import { ref } from 'vue'
import { ojtCertificateApi } from '../services/api.js'
import { ElMessage } from 'element-plus'

export function useOJTCertificate() {
    const loading = ref(false)
    const saving = ref(false)
    const printing = ref(false)
    const items = ref([])

    const fetchAll = async () => {
        loading.value = true
        try {
            const res = await ojtCertificateApi.getAll()
            items.value = res.data?.data ?? []
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to load OJT records')
            items.value = []
        } finally { loading.value = false }
    }

    const createItem = async (payload) => {
        saving.value = true
        try {
            const res = await ojtCertificateApi.create(payload)
            ElMessage.success('OJT information added')
            return res.data?.data
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to add OJT information')
            throw e
        } finally { saving.value = false }
    }

    const updateItem = async (id, payload) => {
        saving.value = true
        try {
            const res = await ojtCertificateApi.update(id, payload)
            ElMessage.success('OJT information updated')
            return res.data?.data
        } catch (e) {
            console.error(e)
            ElMessage.error('Failed to update OJT information')
            throw e
        } finally { saving.value = false }
    }

    const printItem = async (id) => {
        printing.value = true
        try {
            const res = await ojtCertificateApi.print(id)
            const blob = new Blob([res.data], { type: 'application/pdf' })
            return URL.createObjectURL(blob)
        } catch (e) {
            try {
                if (e?.response?.data instanceof Blob) {
                    const text = await e.response.data.text()
                    ElMessage.error(text.slice(0, 300) || 'Server error while generating OJT certificate')
                } else { ElMessage.error('Failed to generate OJT certificate') }
            } catch { ElMessage.error('Failed to generate OJT certificate') }
            throw e
        } finally { printing.value = false }
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

    return { loading, saving, printing, items, fetchAll, createItem, updateItem, printItem, downloadPDFFromBlob }
}


