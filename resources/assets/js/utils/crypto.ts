import { md5 as baseMd5 } from 'js-md5'

export const uuid = () => {
  if (typeof window === 'undefined') {
    // @ts-ignore
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, c =>
      (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16)
    )
  }

  return typeof window.crypto?.randomUUID === 'function'
    ? window.crypto.randomUUID()
    : URL.createObjectURL(new Blob([])).split(/[:\/]/g).pop()
}

export const md5 = (str: string) => baseMd5(str)

export const base64Encode = (str: string) => {
  return btoa(String.fromCodePoint(...(new TextEncoder().encode(str))))
}

export const base64Decode = (str: string) => {
  return new TextDecoder().decode(Uint8Array.from(atob(str), c => c.codePointAt(0)!))
}
