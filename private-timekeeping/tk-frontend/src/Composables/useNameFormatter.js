function normalizeToken(value) {
  return String(value || '').trim()
}

function stripTrailingStandaloneDot(value) {
  // Handles backend names ending with a blank middle initial artifact, e.g. "LAST, FIRST ."
  return String(value || '').replace(/\s+\.$/, '').trim()
}

function pickFirstText(...values) {
  for (const value of values) {
    if (value == null) continue
    const text = String(value).trim()
    if (!text || text.toLowerCase() === 'null' || text.toLowerCase() === 'undefined') continue
    return text
  }
  return ''
}

function splitTokens(value) {
  return normalizeToken(value).split(/\s+/).filter(Boolean)
}

function normalizeAlphaToken(token) {
  return String(token || '').replace(/\./g, '').trim().toUpperCase()
}

function isInitialToken(token) {
  return /^[A-Za-z]$/.test(token) || /^[A-Za-z]\.$/.test(token)
}

function normalizeInitial(token) {
  const cleaned = String(token || '').replace(/\./g, '').trim().toUpperCase()
  if (!cleaned) return ''
  return `${cleaned[0]}.`
}

const LAST_NAME_PARTICLES = new Set([
  'DE', 'DELA', 'DEL', 'LA', 'LAS', 'LOS',
  'DA', 'DAS', 'DO', 'DOS', 'DI', 'DU',
  'VAN', 'VON', 'SAN', 'SANTA',
  'MC', 'MAC', 'BIN', 'AL'
])

function splitNameAndLastTokens(parts) {
  if (!Array.isArray(parts) || parts.length < 2) {
    return { nameTokens: parts || [], lastTokens: [] }
  }

  let start = parts.length - 1
  while (start - 1 >= 0) {
    const prev = normalizeAlphaToken(parts[start - 1])
    if (!LAST_NAME_PARTICLES.has(prev)) break
    start -= 1
  }

  return {
    nameTokens: parts.slice(0, start),
    lastTokens: parts.slice(start),
  }
}

function middleInitials(middleName) {
  const tokens = splitTokens(middleName)
  if (!tokens.length) return ''
  return tokens.map(normalizeInitial).filter(Boolean).join(' ')
}

export function formatNameFromParts({ firstName, middleName, lastName } = {}) {
  const first = normalizeToken(firstName)
  const last = normalizeToken(lastName)
  const mi = middleInitials(middleName)

  if (!first && !last) return ''
  if (!last) return first
  if (!first) return last
  return mi ? `${last}, ${first} ${mi}` : `${last}, ${first}`
}

export function formatNameFromString(rawName) {
  const raw = stripTrailingStandaloneDot(normalizeToken(rawName))
  if (!raw) return ''
  if (raw.includes(',')) return stripTrailingStandaloneDot(raw)

  const parts = splitTokens(raw)
  if (parts.length < 2) return raw

  const { nameTokens, lastTokens } = splitNameAndLastTokens(parts)
  const last = lastTokens.join(' ').trim()

  const firstTokens = []
  const middleTokens = []
  for (const token of nameTokens) {
    if (isInitialToken(token)) {
      middleTokens.push(normalizeInitial(token))
    } else {
      firstTokens.push(token)
    }
  }

  const first = firstTokens.join(' ').trim()
  if (!first) return stripTrailingStandaloneDot(raw)
  const middle = middleTokens.length ? ` ${middleTokens.join(' ')}` : ''
  return `${last}, ${first}${middle}`
}

export function formatEmployeeName(employee, fallback = '') {
  const emp = employee || {}
  const firstName = pickFirstText(emp.first_name, emp.firstname, emp.firstName)
  const middleName = pickFirstText(emp.middle_name, emp.middlename, emp.middleName)
  const lastName = pickFirstText(emp.last_name, emp.lastname, emp.lastName)

  const fromParts = formatNameFromParts({ firstName, middleName, lastName })
  if (fromParts) return fromParts

  const rawName = pickFirstText(emp.name, emp.employee_name, emp.full_name, fallback)
  return formatNameFromString(rawName)
}
