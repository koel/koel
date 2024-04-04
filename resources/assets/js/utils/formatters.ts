/**
 * Convert a duration in seconds into a human-readable format i.e. "x hr y min z sec".
 * Only show hours if the duration is longer than an hour.
 * Also, only show seconds if the duration is less than an hour.
 */
export const secondsToHumanReadable = (total: number) => {
  total = Math.round(total)

  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total - hours * 3600) / 60)
  const seconds = total - hours * 3600 - minutes * 60

  const parts: string[] = []

  if (hours > 0) {
    parts.push(`${hours} hr`)
  }

  if (minutes > 0) {
    parts.push(`${minutes} min`)
  }

  if (seconds > 0 && hours < 1) {
    parts.push(`${seconds} sec`)
  }

  return parts.join(' ') || '0 sec'
}

/**
 * Convert a duration in seconds into H:i:s format.
 * Only show hours if the duration is longer than an hour.
 */
export const secondsToHis = (total: number) => {
  total = Math.round(total)
  const parts: string[] = []

  const hours = Math.floor(total / 3600)

  if (hours > 0) {
    parts.push(hours.toString().padStart(2, '0'))
  }

  parts.push((Math.floor((total / 60) % 60)).toString().padStart(2, '0'))
  parts.push((total % 60).toString().padStart(2, '0'))

  return parts.join(':')
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

/**
 * Turn carriage returns (\r) to line feeds (\n) using JavaScript's implicit DOM-writing behavior
 */
export const cr2lf = (str: string) => {
  const div = document.createElement('div')
  div.innerHTML = str
  return div.innerHTML
}

export const slugToTitle = (slug: string, separator = '-') => {
  let title = slug.split(separator).map(w => w.charAt(0).toUpperCase() + w.substring(1).toLowerCase()).join(' ')
  return title.replace(/\s+/g, ' ').trim()
}

export const pluralize = (count: any[] | number | undefined, singular: string) => {
  count = Array.isArray(count) ? count.length : (count ?? 0)
  return count === 1 ? `${count} ${singular}` : `${count.toLocaleString()} ${singular}s`
}
