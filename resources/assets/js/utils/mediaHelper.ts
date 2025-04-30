export const acceptedMediaTypes = [
  'audio/aac',
  'audio/aiff',
  'audio/x-aiff',
  'audio/flac',
  'audio/mp3',
  'audio/mp4',
  'audio/mpeg',
  'audio/ogg',
  'audio/opus',
  'audio/x-aac',
  'audio/x-aiff',
  'audio/x-flac',
]

interface FileSignature {
  mimeTypes: string[]
  check: (bytes: Uint8Array) => boolean
}

const audioSignatures: FileSignature[] = [
  {
    mimeTypes: ['audio/aiff', 'audio/x-aiff'],
    check: bytes => (
      String.fromCharCode(...bytes.slice(0, 4)) === 'FORM'
      && ['AIFF', 'AIFC'].includes(String.fromCharCode(...bytes.slice(8, 12)))
    ),
  },
  {
    mimeTypes: ['audio/flac', 'audio/x-flac'],
    check: bytes => String.fromCharCode(...bytes.slice(0, 4)) === 'fLaC',
  },
  {
    mimeTypes: ['audio/mpeg', 'audio/mp3'],
    check: bytes => bytes[0] === 0xFF && (bytes[1] & 0xE0) === 0xE0,
  },
  {
    mimeTypes: ['audio/aac', 'audio/x-aac'],
    check: bytes => bytes[0] === 0xFF && (bytes[1] & 0xF6) === 0xF0,
  },
  {
    mimeTypes: ['audio/mp4'],
    check: bytes => (
      String.fromCharCode(...bytes.slice(4, 8)) === 'ftyp'
      && ['M4A ', 'mp42', 'isom'].includes(String.fromCharCode(...bytes.slice(8, 12)))
    ),
  },
  {
    mimeTypes: ['audio/ogg', 'audio/opus'],
    check: bytes => String.fromCharCode(...bytes.slice(0, 4)) === 'OggS',
  },
]

const detectMimeType = async (file: File) => {
  const buffer = await file.slice(0, 64).arrayBuffer()
  const bytes = new Uint8Array(buffer)

  for (const sig of audioSignatures) {
    if (sig.check(bytes)) {
      return sig.mimeTypes[0]
    }
  }

  return null
}

export const acceptsFile = async (file: File) => {
  const type = file.type || await detectMimeType(file)

  return type && acceptedMediaTypes.includes(type)
}
