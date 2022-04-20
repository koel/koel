export const use = <T> (value: T, cb: (arg: T) => void) => {
  if (typeof value === 'undefined' || value === null) {
    return
  }

  cb(value)
}

export const arrayify = <T> (maybeArray: T | Array<T>) => ([] as Array<T>).concat(maybeArray)

// @ts-ignore
export const noop = () => {}
