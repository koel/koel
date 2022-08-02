/**
 * Convert a duration in seconds into H:i:s format.
 * If H is 0, it will be omitted.
 */
export const secondsToHis = (d: number) => {
  d = ~~d

  const s = d % 60
  const sString = s < 10 ? `0${s}` : String(s)

  const i = Math.floor((d / 60) % 60)
  const iString = i < 10 ? `0${i}` : String(i)

  const h = Math.floor(d / 3600)
  const hString = h < 10 ? `0${h}` : String(h)

  return (hString === '00' ? '' : `${hString}:`) + iString + ':' + sString
}

export type ServerValidationError = {
  message: string
  errors: Record<string, string[]>
}
/**
 * Parse the validation error from the server into a flattened array of messages.
 */
export const parseValidationError = (error: ServerValidationError) => {
  let messages: string[] = []

  Object.keys(error.errors).forEach(key => {
    messages = messages.concat(...error.errors[key])
  })

  return messages
}

/**
 * Turn <br> into new line characters.
 */
export const br2nl = (str: string) => str ? str.replace(/<br\s*\/?>/gi, '\n') : ''

export const slugToTitle = (slug: string, separator = '-') => {
  let title = slug.split(separator).map(w => w.charAt(0).toUpperCase() + w.substring(1).toLowerCase()).join(' ')
  return title.replace(/\s+/g, ' ').trim()
}

export const pluralize = (count: number | undefined, singular: string) => {
  count = count ?? 0
  return count === 1 ? `${count} ${singular}` : `${count.toLocaleString()} ${singular}s`
}
