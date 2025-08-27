// A deep-merge function that
// - supports symbols as keys (_.merge doesn't)
// - supports Vue's Ref type without losing reactivity (deepmerge doesn't)
// Credit: https://stackoverflow.com/a/60598589/794641
import { isObject, mergeWith } from 'lodash'

export const deepMerge = (first: object, second: object) => {
  return mergeWith(first, second, (a, b) => {
    if (!isObject(b)) {
      return b
    }

    // @ts-ignore
    return Array.isArray(a) ? [...a, ...b] : { ...a, ...b }
  })
}

export const setPropIfNotExists = (obj: object | null, prop: any, value: any) => {
  if (!obj) {
    return
  }

  if (!Object.prototype.hasOwnProperty.call(obj, prop)) {
    obj[prop] = value
  }
}
