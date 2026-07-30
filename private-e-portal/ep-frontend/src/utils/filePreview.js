export function getFileExtension(fileName) {
  if (!fileName) return ''
  return fileName.split('.').pop()?.toLowerCase() || ''
}

export function isPreviewableFile(fileName) {
  const ext = getFileExtension(fileName)
  return ['pdf', 'txt', 'html', 'htm'].includes(ext)
}

export function isImageFile(fileName) {
  const ext = getFileExtension(fileName)
  return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)
}

export function canPreviewFile(fileName) {
  return isPreviewableFile(fileName) || isImageFile(fileName)
}

export function resolveAttachmentPayload(payload) {
  if (payload?.data?.file_content != null) {
    return payload.data
  }
  return payload?.data || payload
}

export function blobFromAttachmentPayload(payload) {
  const data = resolveAttachmentPayload(payload)
  if (!data?.file_content) {
    throw new Error('File content is not available')
  }

  const byteCharacters = atob(data.file_content)
  const byteNumbers = new Array(byteCharacters.length)
  for (let i = 0; i < byteCharacters.length; i++) {
    byteNumbers[i] = byteCharacters.charCodeAt(i)
  }

  const bytes = new Uint8Array(byteNumbers)
  if (bytes.length === 0) {
    throw new Error('File is empty')
  }

  const filename = String(data.filename || '').toLowerCase()
  const contentType = data.content_type
    || (filename.endsWith('.pdf') ? 'application/pdf' : 'application/octet-stream')

  if (contentType === 'application/pdf' || filename.endsWith('.pdf')) {
    const header = String.fromCharCode(bytes[0], bytes[1], bytes[2], bytes[3])
    if (header !== '%PDF') {
      throw new Error('The file is not a valid PDF')
    }
  }

  return new Blob([bytes], { type: contentType })
}

export function downloadBlob(blob, filename) {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename || 'attachment'
  link.click()
  URL.revokeObjectURL(url)
}
