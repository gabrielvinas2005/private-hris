import { isRef } from 'vue'
import { formatEmployeeName } from './useNameFormatter'

function normalizeString(value) {
  if (value == null) return ''
  return String(value).toLowerCase().trim()
}

/**
 * Check if item matches the search query.
 * @param {Object} item - Row/item to test
 * @param {string} search - Search string
 * @param {string[]} searchFields - Fields to search in; default ['employee_no']. Use ['employee_no', 'name'] to also match by name
 */
function matchesSearch(item, search, searchFields = ['employee_no']) {
  const q = normalizeString(search)
  if (!q) return true
  const fields = Array.isArray(searchFields) && searchFields.length ? searchFields : ['employee_no']
  for (const field of fields) {
    if (field === 'employee_no') {
      const empNo = normalizeString(item.employee_no)
      if (empNo && empNo.includes(q)) return true
    } else if (field === 'name') {
      const name = normalizeString(
        item.name || item.full_name || formatEmployeeName(item)
      )
      if (name && name.includes(q)) return true
    }
  }
  return false
}

function matchesDepartment(item, departmentId) {
  if (departmentId === '' || departmentId === null || departmentId === undefined) return true
  const target = Number(departmentId)
  const value = Number(item.department_id ?? item.departmentId)
  if (!Number.isNaN(target) && !Number.isNaN(value)) return value === target
  // fallback by name if ids are unavailable
  const depName = normalizeString(item.department_name ?? item.department)
  return depName === normalizeString(departmentId)
}

function matchesEmploymentType(item, employmentTypeId) {
  if (employmentTypeId === '' || employmentTypeId === null || employmentTypeId === undefined) return true
  const target = Number(employmentTypeId)
  const value = Number(item.employment_type_id ?? item.employmentTypeId)
  if (!Number.isNaN(target) && !Number.isNaN(value)) return value === target
  // fallback by name if ids are unavailable
  const empName = normalizeString(item.employment_type_name ?? item.employmentStatus)
  return empName === normalizeString(employmentTypeId)
}

function matchesPosition(item, positionId) {
  if (positionId === '' || positionId === null || positionId === undefined) return true
  const target = Number(positionId)
  const value = Number(item.position_id ?? item.positionId)
  if (!Number.isNaN(target) && !Number.isNaN(value)) return value === target
  // fallback by name if ids are unavailable
  const posName = normalizeString(item.position_name ?? item.position)
  return posName === normalizeString(positionId)
}

function matchesYear(item, year) {
  if (year === '' || year === null || year === undefined) return true
  const targetYear = Number(year)
  const itemYear = Number(item.year ?? item.created_year ?? new Date(item.created_at).getFullYear())
  if (!Number.isNaN(targetYear) && !Number.isNaN(itemYear)) return itemYear === targetYear
  return false
}

function matchesStatus(item, status) {
  if (status === '' || status === null || status === undefined) return true
  const itemStatus = normalizeString(item.status ?? item.active)
  return itemStatus === normalizeString(status)
}

// Generic filter matcher
function matchesGeneric(item, filterValue, filterConfig) {
  if (filterValue === '' || filterValue === null || filterValue === undefined) return true
  
  const { type, field, valueField = 'id', labelField = 'name' } = filterConfig
  
  switch (type) {
    case 'exact':
      return item[field] == filterValue
    case 'string':
      return normalizeString(item[field]).includes(normalizeString(filterValue))
    case 'number':
      const target = Number(filterValue)
      const value = Number(item[field])
      return !Number.isNaN(target) && !Number.isNaN(value) && value === target
    case 'boolean':
      return Boolean(item[field]) === Boolean(filterValue)
    case 'array':
      return Array.isArray(item[field]) && item[field].includes(filterValue)
    default:
      return true
  }
}

export function useFilterLogic() {
  const filterEmployees = (items, filters, options = {}) => {
    const list = Array.isArray(items) ? items : []
    const f = isRef(filters) ? (filters.value || {}) : (filters || {})
    const { 
      search, 
      departmentId, 
      employmentTypeId, 
      positionId, 
      year, 
      status,
      ...customFilters 
    } = f
    
    // If onlyEmpNoAndDepartment option is set, only filter by search (Emp No and/or name) and department
    if (options.onlyEmpNoAndDepartment) {
      const searchFields = options.searchFields || ['employee_no']
      return list.filter(item =>
        matchesSearch(item, search, searchFields) &&
        matchesDepartment(item, departmentId)
      )
    }
    
    return list.filter(item =>
      matchesSearch(item, search) &&
      matchesDepartment(item, departmentId) &&
      matchesEmploymentType(item, employmentTypeId) &&
      matchesPosition(item, positionId) &&
      matchesYear(item, year) &&
      matchesStatus(item, status) &&
      // Apply custom filters
      Object.entries(customFilters).every(([key, value]) => {
        // For now, assume custom filters use exact matching
        // This can be extended based on specific needs
        return value === null || value === undefined || value === '' || item[key] == value
      })
    )
  }

  const filterItems = (items, filters, filterConfigs = []) => {
    const list = Array.isArray(items) ? items : []
    const f = isRef(filters) ? (filters.value || {}) : (filters || {})
    
    return list.filter(item => {
      // Apply standard filters
      const standardMatch = matchesSearch(item, f.search) &&
        matchesDepartment(item, f.departmentId) &&
        matchesEmploymentType(item, f.employmentTypeId) &&
        matchesPosition(item, f.positionId) &&
        matchesYear(item, f.year) &&
        matchesStatus(item, f.status)
      
      if (!standardMatch) return false
      
      // Apply custom filters with their configurations
      return filterConfigs.every(config => {
        const filterValue = f[config.key]
        return matchesGeneric(item, filterValue, config)
      })
    })
  }

  return { 
    filterEmployees,
    filterItems,
    // Export individual matchers for custom use
    matchesSearch,
    matchesDepartment,
    matchesEmploymentType,
    matchesPosition,
    matchesYear,
    matchesStatus,
    matchesGeneric
  }
}

export default useFilterLogic


