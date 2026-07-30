// Duplicate content detected below; keeping only a single implementation
import { ref } from 'vue'

export function useSortingLogic() {
  const sortPriority = ref([])
  const sortOrders = ref({})

  // Default sorting: last name ascending.
  const extractLastName = (item) => {
    if (!item) return ''
    const direct =
      item.last_name ?? item.lastName ?? item.lastNameStr ?? item.lastNameString ?? ''
    const directStr = String(direct || '').trim()
    if (directStr) return directStr

    const n = String(item.name ?? item.full_name ?? item.employee_name ?? '').trim()
    if (!n) return ''

    // "LAST, FIRST ..." format
    if (n.includes(',')) return n.split(',')[0].trim()

    // Fallback: "FIRST ... LAST" format
    const parts = n.split(/\s+/).filter(Boolean)
    return (parts[parts.length - 1] ?? '').trim()
  }

  const onSortChange = ({ prop, order }) => {
    if (!prop || !order) {
      const idx = sortPriority.value.indexOf(prop)
      if (idx !== -1) sortPriority.value.splice(idx, 1)
      const next = { ...sortOrders.value }
      delete next[prop]
      sortOrders.value = next
      return
    }

    sortOrders.value = { ...sortOrders.value, [prop]: order }

    const existingIdx = sortPriority.value.indexOf(prop)
    if (existingIdx !== -1) sortPriority.value.splice(existingIdx, 1)
    sortPriority.value.unshift(prop)
  }

  const normalizeNameForSort = (fullName) => {
    const trimmed = (fullName || '').trim()
    if (!trimmed) return ''
    // If backend already provides "LAST, FIRST ..." then keep the string as-is
    // (but lower-case for consistent comparisons).
    if (trimmed.includes(',')) return trimmed.toLowerCase()
    const parts = trimmed.split(/\s+/)
    if (parts.length === 1) return trimmed.toLowerCase()
    const last = (parts[parts.length - 1] || '').toLowerCase()
    const rest = parts.slice(0, -1).join(' ').toLowerCase()
    return `${last}, ${rest}`
  }

  const sortArray = (data) => {
    const arr = Array.isArray(data) ? [...data] : []
    if (!sortPriority.value.length) {
      // If nothing is selected, apply default sorting by last name.
      return arr.sort((a, b) => {
        const lastA = String(extractLastName(a)).toLowerCase()
        const lastB = String(extractLastName(b)).toLowerCase()
        return lastA.localeCompare(lastB)
      })
    }
    arr.sort((a, b) => {
      for (const prop of sortPriority.value) {
        const order = sortOrders.value[prop]
        if (!order) continue
        const dir = order === 'descending' ? -1 : 1
        let va = a[prop]
        let vb = b[prop]

        if (va == null && vb == null) continue
        if (va == null) return -dir
        if (vb == null) return dir

        const compareNumeric = (numA, numB) => {
          const cmpNum = numA - numB
          if (cmpNum !== 0) return cmpNum * dir
          return 0
        }

        if (va instanceof Date || vb instanceof Date) {
          const timeA = va instanceof Date ? va.getTime() : new Date(va).getTime()
          const timeB = vb instanceof Date ? vb.getTime() : new Date(vb).getTime()
          const cmpDate = compareNumeric(timeA || 0, timeB || 0)
          if (cmpDate !== 0) return cmpDate
          continue
        }

        if (typeof va === 'number' && typeof vb === 'number') {
          const cmpNumber = compareNumeric(va, vb)
          if (cmpNumber !== 0) return cmpNumber
          continue
        }

        // Handle different property types for sorting
        if (prop === 'employee_no' || prop === 'usercode') {
          va = String(a[prop] ?? '')
          vb = String(b[prop] ?? '')
        } else if (prop === 'employee_name' || prop === 'name') {
          va = normalizeNameForSort(a[prop])
          vb = normalizeNameForSort(b[prop])
        } else if (prop === 'department') {
          va = (a.department ?? '').toString().toLowerCase()
          vb = (b.department ?? '').toString().toLowerCase()
        } else {
          va = (va ?? '').toString().toLowerCase()
          vb = (vb ?? '').toString().toLowerCase()
        }
        
        const cmp = va.localeCompare(vb)
        if (cmp !== 0) return cmp * dir

        // Within the same department or employment type, always order employees A–Z
        // (applies whether department/type is ascending or descending).
        if (
          prop === 'department' ||
          prop === 'displayDepartment' ||
          prop === 'employment_type' ||
          prop === 'employment_type_name'
        ) {
          const na = normalizeNameForSort(
            a.name ?? a.full_name ?? a.employee_name ?? ''
          )
          const nb = normalizeNameForSort(
            b.name ?? b.full_name ?? b.employee_name ?? ''
          )
          const nameCmp = na.localeCompare(nb)
          if (nameCmp !== 0) return nameCmp
        }
      }
      return 0
    })
    return arr
  }

  return { sortPriority, sortOrders, onSortChange, sortArray }
}

export default useSortingLogic
// Removed duplicate implementation
