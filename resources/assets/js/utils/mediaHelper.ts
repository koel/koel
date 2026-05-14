export const acceptedExtensions = window.KOEL.accepted_audio_extensions

const getFileExtension = (filename: string): string | null => {
  const match = filename.toLowerCase().match(/\.([a-z0-9+]+)$/)
  return match ? match[1] : null
}

export const acceptsFile = (file: File): boolean => {
  const extension = getFileExtension(file.name)

  return Boolean(extension) && acceptedExtensions.includes(extension!)
}
