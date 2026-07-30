import { ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import ApiService from '../Services/api'
import { API_CONFIG } from '../config/api'

export const MAX_DOWNLOADABLE_FILE_MB = 10
const MAX_FILE_SIZE_BYTES = MAX_DOWNLOADABLE_FILE_MB * 1024 * 1024

function formatFileSize(bytes) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function humanizeFileError(message) {
  if (/greater than|too large|max:\d+/i.test(message)) {
    return `File is too large. Maximum allowed size is ${MAX_DOWNLOADABLE_FILE_MB} MB.`
  }
  return message
}

export function getDownloadableDocErrorMessage(res) {
  if (!res) return 'Failed to save document. Please try again.'

  if (res.status === 413) {
    return `File is too large. Maximum allowed size is ${MAX_DOWNLOADABLE_FILE_MB} MB.`
  }

  const errors = res.errors
  if (errors && typeof errors === 'object') {
    const fileErr = errors.file
    if (Array.isArray(fileErr) && fileErr[0]) return humanizeFileError(fileErr[0])
    if (typeof fileErr === 'string') return humanizeFileError(fileErr)

    const firstKey = Object.keys(errors)[0]
    const first = errors[firstKey]
    if (Array.isArray(first) && first[0]) return humanizeFileError(first[0])
    if (typeof first === 'string') return humanizeFileError(first)
  }

  let msg = (res.message || '').trim()
  if (!msg) return 'Failed to save document. Please try again.'

  if (msg.startsWith('<') || msg.includes('<!DOCTYPE')) {
    if (/too large|post too large|content length|upload_max_filesize/i.test(msg)) {
      return `File is too large. Maximum allowed size is ${MAX_DOWNLOADABLE_FILE_MB} MB.`
    }
    return 'Upload failed. The file may be too large or the server could not process it. Please use a file under 10 MB.'
  }

  return humanizeFileError(msg)
}

export function validateDownloadableFile(file) {
  if (!file || !(file instanceof File)) return null
  if (file.size > MAX_FILE_SIZE_BYTES) {
    return `File is too large (${formatFileSize(file.size)}). Maximum allowed size is ${MAX_DOWNLOADABLE_FILE_MB} MB.`
  }
  return null
}

export function useDownloadableDocs() {
  const docs = ref([])
  const loading = ref(false)
  const formLoading = ref(false)

  async function fetchDocs() {
    loading.value = true
    try {
      const res = await ApiService.getDownloadableDocs()
      const data = res?.data || []
      const baseUrl = API_CONFIG.BASE_URL || ''
      const apiBase = baseUrl.replace(/\/api\/?$/, '') + '/api'

      docs.value = data.map(row => {
        let fileName = ''
        try {
          const parsed = JSON.parse(row.FormFile || '{}')
          if (parsed && parsed.name) {
            fileName = parsed.name
          }
        } catch (e) {
          fileName = row.FormFile || ''
        }

        return {
          id: row.FormId,
          name: row.FormName,
          fileName,
          downloadUrl: `${apiBase}/downloadable-forms/${row.FormId}/download`,
          previewUrl: `${apiBase}/downloadable-forms/${row.FormId}/preview`,
          created_at: row.created_at,
          updated_at: row.updated_at
        }
      })
    } catch (e) {
      console.error('Error loading downloadable docs:', e)
      ElMessage.error('Failed to load downloadable documents')
      docs.value = []
    } finally {
      loading.value = false
    }
  }

  async function saveDoc(payload) {
    formLoading.value = true
    try {
      const fileValidationError = payload.file ? validateDownloadableFile(payload.file) : null
      if (fileValidationError) {
        return { success: false, message: fileValidationError }
      }

      const formData = new FormData()
      formData.append('FormName', payload.name)
      if (payload.file instanceof File) {
        formData.append('file', payload.file)
      }

      let res
      if (payload.id) {
        res = await ApiService.updateDownloadableDoc(payload.id, formData)
      } else {
        res = await ApiService.saveDownloadableDoc(formData)
      }

      if (!res?.success) {
        return { success: false, message: getDownloadableDocErrorMessage(res) }
      }

      ElMessage.success(res?.message || 'Downloadable document saved successfully')
      await fetchDocs()
      return { success: true }
    } catch (e) {
      console.error('Error saving downloadable doc:', e)
      return {
        success: false,
        message: getDownloadableDocErrorMessage(e) || 'Failed to save document'
      }
    } finally {
      formLoading.value = false
    }
  }

  async function deleteDoc(id) {
    try {
      await ElMessageBox.confirm(
        'Are you sure you want to delete this downloadable document?',
        'Confirm Deletion',
        {
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          type: 'warning'
        }
      )

      const res = await ApiService.deleteDownloadableDoc(id)
      if (!res?.success) throw new Error(res?.message || 'Delete failed')
      ElMessage.success(res?.message || 'Downloadable document deleted successfully')
      await fetchDocs()
      return { success: true }
    } catch (e) {
      if (e === 'cancel') {
        return { success: false, cancelled: true }
      }
      console.error('Error deleting downloadable doc:', e)
      ElMessage.error(e?.message || 'Failed to delete document')
      return { success: false }
    }
  }

  return {
    docs,
    loading,
    formLoading,
    fetchDocs,
    saveDoc,
    deleteDoc
  }
}

