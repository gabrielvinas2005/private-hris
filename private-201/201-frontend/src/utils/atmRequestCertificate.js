export const DEFAULT_ATM_RECIPIENT_NAME = 'MS. ESTRELITA S. GERONIMO'
export const DEFAULT_ATM_SIGNATORY = 'NELLY NITA N. DILLERA, CESO III'
export const DEFAULT_ATM_SIGNATORY_POSITION = 'Executive Director'

export const buildSalutationFromRecipientName = (recipientName) => {
  const parts = String(recipientName || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!parts.length) {
    return 'Dear Ms. Geronimo:'
  }

  const titleToken = parts[0].toUpperCase().replace(/\.$/, '')
  let title = 'Ms.'
  if (titleToken.startsWith('MR')) title = 'Mr.'
  else if (titleToken.startsWith('MRS')) title = 'Mrs.'
  else if (titleToken.startsWith('MS')) title = 'Ms.'

  const lastName = parts[parts.length - 1]
  const formattedLastName = lastName.charAt(0).toUpperCase() + lastName.slice(1).toLowerCase()

  return `Dear ${title} ${formattedLastName}:`
}
