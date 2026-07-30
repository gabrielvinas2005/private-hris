import { ref, computed } from 'vue'
import { regretLetterApi } from '../services/api.js'
import { ElMessage } from 'element-plus'


export function useRegretLetter() {

    const loading = ref(false)
    const data = ref([])


    const totalItems = computed( ()=>{
        return data.value.length
    })


    const fetchData = async () => {
        loading.value = true
        try {
            const res = await regretLetterApi.index()
            data.value = res.data?.data ?? []
            
        }catch (e){
            ElMessage.error('Failed to load data')
        }finally{
            loading.value = false
        }
    }


    const previewUrl = ref(null)
    const previewFilename = ref(null)

    const generateLetter = async (id) => {
        loading.value = true
        try {
            const res = await regretLetterApi.generateLetter(id)
            
            // Create blob URL for preview
            const blob = new Blob([res.data], { type: 'application/pdf' })
            const url = URL.createObjectURL(blob)
            
            // Store preview URL and filename
            previewUrl.value = url
            previewFilename.value = `regret_letter_${id}_${new Date().toISOString().split('T')[0]}.pdf`
            
            ElMessage.success('Regret letter generated successfully')
            return url
        } catch (e) {
            ElMessage.error('Failed to generate regret letter')
            throw e
        } finally {
            loading.value = false
        }
    }

    const downloadLetter = () => {
        if (!previewUrl.value || !previewFilename.value) {
            ElMessage.warning('No letter to download')
            return
        }
        
        const link = document.createElement('a')
        link.href = previewUrl.value
        link.download = previewFilename.value
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
    }

    const downloadWord = async (id) => {
        if (!id) {
            ElMessage.warning('No applicant selected')
            return
        }
        
        loading.value = true
        try {
            const res = await regretLetterApi.generateWord(id)
            const blob = new Blob([res.data], { 
                type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' 
            })
            const url = URL.createObjectURL(blob)
            const filename = `regret_letter_${id}_${new Date().toISOString().split('T')[0]}.docx`
            
            const link = document.createElement('a')
            link.href = url
            link.download = filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            URL.revokeObjectURL(url)
            
            ElMessage.success('Word document downloaded successfully')
        } catch (e) {
            ElMessage.error('Failed to download Word document')
            throw e
        } finally {
            loading.value = false
        }
    }

    const clearPreview = () => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value)
            previewUrl.value = null
            previewFilename.value = null
        }
    }
    
    return {
        loading,
        data,
        totalItems,
        fetchData,
        generateLetter,
        previewUrl,
        previewFilename,
        downloadLetter,
        downloadWord,
        clearPreview
    }

}

