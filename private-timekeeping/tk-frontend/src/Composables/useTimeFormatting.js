export function formatHours(value) {
  if (value === null || value === undefined || value === '') return ''
  const num = Number(value)
  if (!Number.isFinite(num)) return ''
  const hours = Math.floor(num)
  const minutes = Math.round((num - hours) * 60)
  if (minutes === 0) return `${hours} hours`
  return `${hours} hours and ${minutes} mins`
}

// Format hours for display (e.g., "2 hrs 32 mins" or "1 hr 2 mins")
// Uses singular "hr" when hours = 1, plural "hrs" otherwise
// Uses "mins" for minutes (always plural)
export function formatHoursForDisplay(value) {
  if (value === null || value === undefined || value === '') return '0 hrs'
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '0 hrs'
  
  const hours = Math.floor(num)
  const minutes = Math.round((num - hours) * 60)
  
  if (hours === 0 && minutes === 0) {
    return '0 hrs'
  }
  
  if (hours === 0) {
    return `${minutes} mins`
  }
  
  if (minutes === 0) {
    return hours === 1 ? '1 hr' : `${hours} hrs`
  }
  
  const hourText = hours === 1 ? '1 hr' : `${hours} hrs`
  return `${hourText} ${minutes} mins`
}

// Format hours and minutes for Late/Undertime display
// - If 0: "None"
// - If below 1 hour: "32 mins" or "1 min"
// - If above 1 hour: "1 hr 32 mins" or "2 hrs 32 mins" or "2 hrs 1 min" or "1 hr 1 min"
export function formatHoursMinsForDisplay(value) {
  if (value === null || value === undefined || value === '') return 'None'
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return 'None'
  
  const hours = Math.floor(num)
  const minutes = Math.round((num - hours) * 60)
  
  // If both hours and minutes are 0, return "None"
  if (hours === 0 && minutes === 0) {
    return 'None'
  }
  
  // If below 1 hour, show only minutes
  if (hours === 0) {
    return minutes === 1 ? '1 min' : `${minutes} mins`
  }
  
  // If above 1 hour
  const hourText = hours === 1 ? '1 hr' : `${hours} hrs`
  
  // If no minutes, show only hours
  if (minutes === 0) {
    return hourText
  }
  
  // If both hours and minutes, show both
  const minuteText = minutes === 1 ? '1 min' : `${minutes} mins`
  return `${hourText} ${minuteText}`
}

// Format days for display (e.g., "1 day" or "2 days")
export function formatDaysForDisplay(value) {
  if (value === null || value === undefined || value === '') return '0 days'
  const num = Number(value)
  if (!Number.isFinite(num) || num === 0) return '0 days'
  
  // Round to 2 decimal places for display
  const rounded = Math.round(num * 100) / 100
  
  if (rounded === 1) {
    return '1 day'
  }
  
  return `${rounded} days`
}

// Format a time value into AM/PM text.
// Accepts:
// - "HH:mm" or "HH:mm:ss"
// - A Date instance, timestamp, or any string parsable by Date
// Returns empty string for invalid inputs
export function formatTime(value) {
  if (value === null || value === undefined || value === '') return ''

  // Handle Date objects or timestamps
  if (value instanceof Date || typeof value === 'number') {
    const d = value instanceof Date ? value : new Date(value)
    if (Number.isNaN(d.getTime())) return ''
    const hours = d.getHours()
    const minutes = d.getMinutes()
    const period = hours >= 12 ? 'P.M.' : 'A.M.'
    const hour12 = hours % 12 || 12
    const mm = String(minutes).padStart(2, '0')
    return `${String(hour12).padStart(2, '0')}:${mm} ${period}`
  }

  const str = String(value).trim()

  // If value is a plain time string like HH:mm or HH:mm:ss
  const timeMatch = str.match(/^\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*$/)
  if (timeMatch) {
    let hour = Number(timeMatch[1])
    const minute = timeMatch[2]
    if (!Number.isFinite(hour)) return ''
    const period = hour >= 12 ? 'P.M.' : 'A.M.'
    hour = hour % 12
    if (hour === 0) hour = 12
    return `${String(hour).padStart(2, '0')}:${minute} ${period}`
  }

  // Otherwise, attempt to parse as a datetime string
  const d = new Date(str.includes('T') || str.includes(' ') ? str : `1970-01-01T${str}`)
  if (Number.isNaN(d.getTime())) return ''
  const hours = d.getHours()
  const minutes = d.getMinutes()
  const period = hours >= 12 ? 'P.M.' : 'A.M.'
  const hour12 = hours % 12 || 12
  const mm = String(minutes).padStart(2, '0')
  return `${String(hour12).padStart(2, '0')}:${mm} ${period}`
}

export default function useTimeFormatting() {
  return { formatHours, formatTime, formatHoursForDisplay, formatHoursMinsForDisplay, formatDaysForDisplay }
}
