/**
 * MinutesToDayFraction Lookup Table Utility
 * Provides conversion functions between day fractions and minutes using the lookup table
 * Based on 8-hour workday: 1 hour = 0.125 day fraction
 */

// MinutesToDayFraction lookup table mapping (minutes -> day_fraction)
// Matches the database MinutesToDayFraction table
export const MINUTES_TO_DAY_FRACTION = {
  0: 0.000, 1: 0.002, 2: 0.004, 3: 0.006, 4: 0.008, 5: 0.010,
  6: 0.012, 7: 0.015, 8: 0.017, 9: 0.019, 10: 0.021, 11: 0.023,
  12: 0.025, 13: 0.027, 14: 0.029, 15: 0.031, 16: 0.033, 17: 0.035,
  18: 0.037, 19: 0.040, 20: 0.042, 21: 0.044, 22: 0.046, 23: 0.048,
  24: 0.050, 25: 0.052, 26: 0.054, 27: 0.056, 28: 0.058, 29: 0.060,
  30: 0.062, 31: 0.065, 32: 0.067, 33: 0.069, 34: 0.071, 35: 0.073,
  36: 0.075, 37: 0.077, 38: 0.079, 39: 0.081, 40: 0.083, 41: 0.085,
  42: 0.087, 43: 0.090, 44: 0.092, 45: 0.094, 46: 0.096, 47: 0.098,
  48: 0.100, 49: 0.102, 50: 0.104, 51: 0.106, 52: 0.108, 53: 0.110,
  54: 0.112, 55: 0.115, 56: 0.117, 57: 0.119, 58: 0.121, 59: 0.123
}

/**
 * Convert day fraction (based on 8-hour workday) to total minutes
 * Uses the MinutesToDayFraction lookup table to match backend logic
 * 
 * @param {number} dayFraction - The day fraction value (e.g., 0.046 = 22 minutes)
 * @returns {number} Total minutes
 */
export function dayFractionToMinutes(dayFraction) {
  if (!dayFraction || dayFraction <= 0) return 0
  
  const rounded = Math.round(dayFraction * 1000) / 1000 // Round to 3 decimals
  
  // Calculate hours portion (each hour = 0.125 day)
  const hours = Math.floor(rounded / 0.125)
  const remainingFraction = Math.round((rounded - (hours * 0.125)) * 1000) / 1000
  
  // Find the exact or closest match in the lookup table for remaining fraction
  let minutes = 0
  if (remainingFraction > 0.0001) {
    // Build reverse lookup: fraction -> minutes
    const fractionToMinutes = {}
    for (const [mins, frac] of Object.entries(MINUTES_TO_DAY_FRACTION)) {
      fractionToMinutes[frac] = parseInt(mins)
    }
    
    // Get all day fractions from lookup table and sort them
    const fractions = Object.keys(fractionToMinutes).map(parseFloat).sort((a, b) => a - b)
    
    // First, try to find an exact match (within 0.001 tolerance)
    let exactMatch = null
    for (const fraction of fractions) {
      if (Math.abs(remainingFraction - fraction) <= 0.001) {
        exactMatch = fraction
        break
      }
    }
    
    if (exactMatch !== null) {
      minutes = fractionToMinutes[exactMatch] || 0
    } else {
      // Find closest match
      let closestFraction = fractions[0]
      let minDiff = Math.abs(remainingFraction - closestFraction)
      
      for (const fraction of fractions) {
        const diff = Math.abs(remainingFraction - fraction)
        if (diff < minDiff) {
          minDiff = diff
          closestFraction = fraction
        }
      }
      
      minutes = fractionToMinutes[closestFraction] || 0
    }
  }
  
  return (hours * 60) + minutes
}

/**
 * Convert total minutes to day fraction using MinutesToDayFraction lookup table
 * Uses the same logic as database function fn_MinutesToDayFraction
 * 
 * @param {number} totalMinutes - Total minutes to convert
 * @returns {number} Day fraction (rounded to 3 decimals)
 */
export function minutesToDayFraction(totalMinutes) {
  if (!totalMinutes || totalMinutes <= 0) return 0.000
  
  const total = Math.round(totalMinutes)
  const hours = Math.floor(total / 60)
  const remainingMinutes = total % 60
  
  // Hours fraction: each hour = 0.125 day
  const hoursFraction = hours * 0.125
  
  // Get minutes fraction from lookup table
  const minutesFraction = MINUTES_TO_DAY_FRACTION[remainingMinutes] || 0
  
  return Math.round((hoursFraction + minutesFraction) * 1000) / 1000
}

/**
 * Convert day fraction to hours and minutes
 * 
 * @param {number} dayFraction - The day fraction value
 * @returns {Object} Object with hours, minutes, and total_minutes
 */
export function dayFractionToHoursMinutes(dayFraction) {
  if (!dayFraction || dayFraction <= 0) {
    return { hours: 0, minutes: 0, total_minutes: 0 }
  }
  
  const totalMinutes = dayFractionToMinutes(dayFraction)
  const hours = Math.floor(totalMinutes / 60)
  const minutes = totalMinutes % 60
  
  return {
    hours,
    minutes,
    total_minutes: totalMinutes
  }
}

/**
 * Calculate total offset days from late, undertime, and absent using lookup table
 * Converts each to minutes, sums them, then converts back to day fraction
 * 
 * @param {number} late - Late day fraction
 * @param {number} undertime - Undertime day fraction
 * @param {number} absent - Absent days
 * @returns {number} Total days (rounded to 3 decimals)
 */
export function calculateOffsetTotalDays(late = 0, undertime = 0, absent = 0) {
  // Convert late day fraction to minutes
  const lateMinutes = dayFractionToMinutes(late)
  
  // Convert undertime day fraction to minutes
  const undertimeMinutes = dayFractionToMinutes(undertime)
  
  // Convert absent days to minutes (1 day = 8 hours = 480 minutes)
  const absentMinutes = Math.round(absent * 480)
  
  // Sum all minutes
  const totalMinutes = lateMinutes + undertimeMinutes + absentMinutes
  
  // Convert total minutes back to day fraction using lookup table
  return minutesToDayFraction(totalMinutes)
}

/**
 * Format day fraction as human-readable string
 * 
 * @param {number} dayFraction - The day fraction value
 * @returns {string} Human-readable format (e.g., "7 hrs and 51 mins", "8 mins", "1 hr")
 */
export function formatDayFractionHuman(dayFraction) {
  const result = dayFractionToHoursMinutes(dayFraction)
  const hours = result.hours
  const minutes = result.minutes

  const hLabel = hours === 1 ? 'hr' : 'hrs'
  const mLabel = minutes === 1 ? 'min' : 'mins'
  
  if (hours > 0 && minutes > 0) {
    return `${hours} ${hLabel} and ${minutes} ${mLabel}`
  }
  if (hours > 0) {
    return `${hours} ${hLabel}`
  }
  if (minutes > 0) {
    return `${minutes} ${mLabel}`
  }
  return "0 mins"
}

