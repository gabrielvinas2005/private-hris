import { API_BASE_URL } from '../config/api'
import { ElMessage } from 'element-plus'
import { ref } from 'vue'

/**
 * Composable for backend report exports (Excel, Word, and HTML Preview)
 * Provides a unified way to export reports from the backend
 */
export function useBackendReportExport() {
  const htmlLoading = ref(false)
  
  /**
   * Get authentication token from localStorage
   */
  function getAuthToken() {
    const rawAuthToken = localStorage.getItem('auth_token')
    const rawDevToken = localStorage.getItem('dev_auth_token')
    let token = null
    
    if (rawAuthToken) {
      try {
        const parsed = JSON.parse(rawAuthToken)
        token = parsed?.token || rawAuthToken
      } catch (_) {
        token = rawAuthToken
      }
    } else if (rawDevToken) {
      try {
        const parsed = JSON.parse(rawDevToken)
        token = parsed?.token || rawDevToken
      } catch (_) {
        token = rawDevToken
      }
    }
    
    return token
  }

  /**
   * Download a blob as a file
   */
  function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)
  }

  /**
   * Export report to Excel via backend
   * @param {string} reportType - The report type (e.g., 'fix_schedule_list', 'shift_schedule_viewer')
   * @param {object} data - The report data
   * @param {string} filename - The filename (without extension)
   */
  async function exportToExcel(reportType, data, filename) {
    try {
      const token = getAuthToken()
      const reportData = {
        report_type: reportType,
        data: data,
        filename: filename
      }
      
      const response = await fetch(`${API_BASE_URL}/reports/excel`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        credentials: 'include',
        body: JSON.stringify(reportData)
      })
      
      if (!response.ok) {
        throw new Error('Failed to generate Excel report')
      }
      
      const blob = await response.blob()
      downloadBlob(blob, `${filename}.xlsx`)
      ElMessage.success('Excel report exported successfully')
    } catch (err) {
      console.error('Excel export failed:', err)
      ElMessage.error('Failed to export Excel report')
      throw err
    }
  }

  /**
   * Export report to Word via backend
   * @param {string} reportType - The report type (e.g., 'fix_schedule_list', 'shift_schedule_viewer')
   * @param {object} data - The report data
   * @param {string} filename - The filename (without extension)
   */
  async function exportToWord(reportType, data, filename) {
    try {
      const token = getAuthToken()
      const reportData = {
        report_type: reportType,
        data: data,
        filename: filename
      }
      
      const response = await fetch(`${API_BASE_URL}/reports/docx`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        credentials: 'include',
        body: JSON.stringify(reportData)
      })
      
      if (!response.ok) {
        throw new Error('Failed to generate Word report')
      }
      
      const blob = await response.blob()
      downloadBlob(blob, `${filename}.docx`)
      ElMessage.success('Word report exported successfully')
    } catch (err) {
      console.error('Word export failed:', err)
      ElMessage.error('Failed to export Word report')
      throw err
    }
  }

  /**
   * Get HTML preview from backend
   * @param {string} reportType - The report type (e.g., 'fix_schedule_list', 'shift_schedule_viewer')
   * @param {object} data - The report data
   * @returns {Promise<string>} HTML content
   */
  async function getHtmlPreview(reportType, data) {
    try {
      htmlLoading.value = true
      const token = getAuthToken()
      const reportData = {
        report_type: reportType,
        data: data
      }
      
      const response = await fetch(`${API_BASE_URL}/reports/html`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        },
        credentials: 'include',
        body: JSON.stringify(reportData)
      })
      
      if (!response.ok) {
        throw new Error('Failed to generate HTML preview')
      }
      
      const result = await response.json()
      return result.html || ''
    } catch (err) {
      console.error('HTML preview generation failed:', err)
      ElMessage.error('Failed to generate HTML preview')
      return ''
    } finally {
      htmlLoading.value = false
    }
  }

  return {
    exportToExcel,
    exportToWord,
    getHtmlPreview,
    htmlLoading
  }
}

