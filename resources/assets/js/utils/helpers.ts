export const use = <T>(value: T, cb: (arg: T) => void): void => {
  if (typeof value === 'undefined' || value === null) {
    return
  }

  cb(value)
}

export const noop = () => {}
