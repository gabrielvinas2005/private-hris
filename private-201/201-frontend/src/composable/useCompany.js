import { ref, computed } from 'vue'
import api from '@/services/api'

const companies = ref([])
const currentCompany = ref(null)
const loading = ref(false)

const hasCompany = computed(() => companies.value.length > 0)
const primaryCompany = computed(() => companies.value[0] || null)

export function useCompany() {
  async function fetchCompanies() {
    loading.value = true
    try {
      const { data } = await api.get('/companies')

      if (data?.success) {
        companies.value = data.data || []
        currentCompany.value = companies.value[0] || null
        return { success: true, data: data.data }
      }

      return { success: false, message: data?.message }
    } catch (error) {
      console.warn('Failed to fetch company data:', error?.message || error)
      return { success: false, message: error.message }
    } finally {
      loading.value = false
    }
  }

  function getLogoUrl(company) {
    const logo = company?.logo
    if (!logo || typeof logo !== 'string') {
      return null
    }

    const trimmed = logo.trim()
    if (!trimmed) {
      return null
    }

    if (trimmed.startsWith('data:image')) {
      return trimmed
    }

    let mime = 'image/jpeg'
    if (trimmed.startsWith('iVBOR')) {
      mime = 'image/png'
    } else if (trimmed.startsWith('R0lGOD')) {
      mime = 'image/gif'
    } else if (trimmed.startsWith('UklGR')) {
      mime = 'image/webp'
    }

    return `data:${mime};base64,${trimmed}`
  }

  function setFavicon(href) {
    let link = document.querySelector('link[rel="icon"]')
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = href
  }

  async function applyCompanyBranding() {
    const result = await fetchCompanies()
    const company = result?.data?.[0] || primaryCompany.value
    const name = company?.name?.trim()

    document.title = name ? `HR Module - ${name}` : 'HR Module'

    const logo = getLogoUrl(company)
    if (logo) {
      setFavicon(logo)
    }

    return company
  }

  return {
    companies,
    currentCompany,
    loading,
    hasCompany,
    primaryCompany,
    fetchCompanies,
    getLogoUrl,
    applyCompanyBranding,
  }
}
