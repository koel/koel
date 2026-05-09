export const uuid = () => {
  if (typeof window === 'undefined') {
    // @ts-ignore
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
      (c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (c / 4)))).toString(16),
    )
  }

  return typeof window.crypto?.randomUUID === 'function'
    ? window.crypto.randomUUID()
    : URL.createObjectURL(new Blob([])).split(/[:/]/g).pop()
}

/**
 * Generate a Crockford-base32 ULID (26 chars: 10-char timestamp + 16-char randomness).
 * Stable identity for client-generated records that the server treats as opaque keys.
 */
export const ulid = () => {
  const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ'

  const encode = (value: bigint, length: number) => {
    let out = ''

    for (let i = length - 1; i >= 0; i--) {
      out += ALPHABET[Number((value >> BigInt(i * 5)) & 0b11111n)]
    }

    return out
  }

  const random = crypto.getRandomValues(new Uint8Array(10))
  const randomness = random.reduce((acc, byte) => (acc << 8n) | BigInt(byte), 0n)

  return encode(BigInt(Date.now()), 10) + encode(randomness, 16)
}

export const sha256 = async (input: string) => {
  const buffer = new TextEncoder().encode(input)
  const digest = await crypto.subtle.digest('SHA-256', buffer)
  return Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join('')
}

export const base64Encode = (str: string) => {
  return btoa(String.fromCodePoint(...new TextEncoder().encode(str)))
}

export const base64Decode = (str: string) => {
  return new TextDecoder().decode(Uint8Array.from(atob(str), c => c.codePointAt(0)!))
}
