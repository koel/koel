export const acceptedExtensions = [
  // Lossy
  'mp3',
  'mp4',
  'm4a',
  'aac',
  'ogg',
  'opus',
  'flac',
  'fla',
  'amr',
  'ac3',
  'dts',
  'ra',
  'rm',
  'wma',
  'au',

  // Lossless and others
  'wav',
  'aiff',
  'aif',
  'aifc',
  'mka',
  'ape',
  'tta',
  'wv',
  'wvc',
  'ofr',
  'ofs',
  'shn',
  'lpac',
  'dsf',
  'dff',
  'spx',
  'dss',
  'aa',
  'vqf',
  'mpc',
  'mp+',
  'voc',
]

const getFileExtension = (filename: string): string | null => {
  const match = filename.toLowerCase().match(/\.([a-z0-9+]+)$/)
  return match ? match[1] : null
}

export const acceptsFile = (file: File): boolean => {
  const extension = getFileExtension(file.name)

  return Boolean(extension) && acceptedExtensions.includes(extension!)
}
