import axios from 'axios'

const emptyCompany = {
  name: '',
  address: '',
  email: '',
  telephone_no: '',
  mobile_no: '',
  branch_code: '',
  logo_data_url: null
}

let company = { ...emptyCompany }
let fetchPromise = null

export function getCompanyPublic() {
  return company
}

export function getCompanyLogo(companyData = company) {
  return companyData?.logo_data_url || null
}

export function getCompanyShortName(companyData = company) {
  return companyData?.branch_code?.trim() || companyData?.name?.trim() || 'Company'
}

export async function fetchCompanyPublic() {
  if (fetchPromise) {
    return fetchPromise
  }

  fetchPromise = (async () => {
    try {
      const res = await axios.get('/company-public')
      const payload = res?.data?.data

      if (res?.data?.success && payload && typeof payload === 'object') {
        company = { ...emptyCompany, ...payload }
      }
    } catch (error) {
      console.warn('Failed to load company info:', error?.message || error)
    }

    return company
  })()

  return fetchPromise
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

export async function applyCompanyBranding() {
  const data = await fetchCompanyPublic()
  const name = data?.name?.trim()

  document.title = name ? `Applicant Portal - ${name}` : 'Applicant Portal'

  const logo = getCompanyLogo(data)
  if (logo) {
    setFavicon(logo)
  }

  return data
}
