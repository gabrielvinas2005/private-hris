import { ref, computed } from 'vue'
import {
  fetchCompanyPublic,
  getCompanyPublic,
  getCompanyLogo,
  getCompanyShortName,
} from '@/services/companyPublic'

const company = ref({ ...getCompanyPublic() })

export function useCompanyBranding() {
  const companyName = computed(() => company.value?.name?.trim() || 'Company')
  const companyAddress = computed(() => company.value?.address?.trim() || '')
  const branchCode = computed(() => company.value?.branch_code?.trim() || '')
  const shortName = computed(() => getCompanyShortName(company.value))
  const companyLogo = computed(() => getCompanyLogo(company.value))

  async function loadCompany() {
    company.value = await fetchCompanyPublic()
    return company.value
  }

  return {
    company,
    companyName,
    companyAddress,
    branchCode,
    shortName,
    companyLogo,
    loadCompany,
  }
}
