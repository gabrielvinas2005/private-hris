import { ref, computed } from 'vue'
import { useToast } from 'vue-toastification'
import ApiService from '../services/api.js'

export function useHolidayCalendar() {
  const toast = useToast()

  const holidays = ref([])
  const loading = ref(false)
  const error = ref(null)
  const selectedYear = ref(new Date().getFullYear())

  /**
   * Fetch holidays from the API for the given year.
   * Falls back to selectedYear if no argument is provided.
   */
  const loadCalendar = async (year = null) => {
    const targetYear = year ?? selectedYear.value
    selectedYear.value = targetYear

    try {
      loading.value = true
      error.value = null

      const response = await ApiService.getHolidayCalendar(targetYear)

      if (!response || !response.success) {
        throw new Error(response?.message || 'Failed to load holiday calendar')
      }

      holidays.value = Array.isArray(response.data) ? response.data : []
    } catch (err) {
      error.value = err.message || 'Failed to load holiday calendar'
      toast.error('Failed to load holiday calendar')
    } finally {
      loading.value = false
    }
  }

  /**
   * Returns an object keyed by month number (1–12) with the month name and its holidays.
   * Only months that have at least one holiday are included.
   */
  const groupedByMonth = computed(() => {
    const MONTH_NAMES = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ]

    const groups = {}

    holidays.value.forEach((h) => {
      if (!h.date) return
      const d = new Date(h.date)
      if (isNaN(d.getTime())) return
      const monthIndex = d.getMonth() // 0-based
      const key = monthIndex + 1       // 1-based

      if (!groups[key]) {
        groups[key] = { monthName: MONTH_NAMES[monthIndex], holidays: [] }
      }
      groups[key].holidays.push(h)
    })

    // Return sorted by month number
    return Object.keys(groups)
      .sort((a, b) => Number(a) - Number(b))
      .reduce((acc, k) => { acc[k] = groups[k]; return acc }, {})
  })

  /** Total number of holidays in the selected year */
  const totalCount = computed(() => holidays.value.length)

  /** Formats a date string as "Day, Month DD" e.g. "Monday, January 01" */
  const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('en-US', {
      weekday: 'long',
      month: 'long',
      day: '2-digit'
    })
  }

  /** Returns a human-readable rate label e.g. "200%" or "130%" */
  const formatRate = (rate) => {
    if (rate == null) return ''
    return `${Math.round((1 + Number(rate)) * 100)}%`
  }

  return {
    holidays,
    loading,
    error,
    selectedYear,
    groupedByMonth,
    totalCount,
    loadCalendar,
    formatDate,
    formatRate
  }
}
