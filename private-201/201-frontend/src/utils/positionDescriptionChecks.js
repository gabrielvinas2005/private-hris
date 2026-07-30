export const defaultStakeholdersChecks = () => ({
  ieo: false,
  ief: false,
  iso: false,
  isf: false,
  ino: false,
  inf: false,
  isto: false,
  istf: false,
  egpo: false,
  egpf: false,
  eoao: false,
  eoaf: false,
  eoto: false,
  eotf: false,
  eots: '',
})

export const defaultWorkingConditionChecks = () => ({
  owo: false,
  owf: false,
  fwo: false,
  fwf: false,
  oto: false,
  otf: false,
  ots: '',
})

export const parseStakeholdersChecks = (value) => {
  const defaults = defaultStakeholdersChecks()
  if (!value || typeof value !== 'string') {
    return defaults
  }

  try {
    const decoded = JSON.parse(value)
    if (!decoded || typeof decoded !== 'object') {
      return defaults
    }
    return {
      ...defaults,
      ...Object.keys(defaults).reduce((acc, key) => {
        if (Object.prototype.hasOwnProperty.call(decoded, key)) {
          acc[key] = key === 'eots' ? String(decoded[key] ?? '') : Boolean(decoded[key])
        }
        return acc
      }, {}),
    }
  } catch {
    return defaults
  }
}

export const parseWorkingConditionChecks = (value) => {
  const defaults = defaultWorkingConditionChecks()
  if (!value || typeof value !== 'string') {
    return defaults
  }

  try {
    const decoded = JSON.parse(value)
    if (!decoded || typeof decoded !== 'object') {
      return defaults
    }
    return {
      ...defaults,
      ...Object.keys(defaults).reduce((acc, key) => {
        if (Object.prototype.hasOwnProperty.call(decoded, key)) {
          acc[key] = key === 'ots' ? String(decoded[key] ?? '') : Boolean(decoded[key])
        }
        return acc
      }, {}),
    }
  } catch {
    return defaults
  }
}

export const serializeStakeholdersChecks = (checks) => JSON.stringify(checks ?? defaultStakeholdersChecks())

export const serializeWorkingConditionChecks = (checks) =>
  JSON.stringify(checks ?? defaultWorkingConditionChecks())
