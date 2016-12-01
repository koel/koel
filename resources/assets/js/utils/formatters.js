/**
 * Convert a duration in seconds into H:i:s format.
 * If H is 0, it will be ommited.
 */
export function secondsToHis (d) {
  d = ~~d

  let s = d % 60

  if (s < 10) {
    s = '0' + s
  }

  let i = Math.floor((d / 60) % 60)

  if (i < 10) {
    i = '0' + i
  }

  let h = Math.floor(d / 3600)

  if (h < 10) {
    h = '0' + h
  }

  return (h === '00' ? '' : h + ':') + i + ':' + s
}

/**
 * Parse the validation error from the server into a flattened array of messages.
 *
 * @param  {Object}  error  The error object in JSON format.
 *
 * @return {Array.<String>}
 */
export function parseValidationError (error) {
  return Object.keys(error).reduce((messages, field) => messages.concat(error[field]), [])
}

/**
 * Turn <br> into new line characters.
 *
 * @param  {string} str
 *
 * @return {string}
 */
export function br2nl (str) {
  return str.replace(/<br\s*[\/]?>/gi, '\n')
}
