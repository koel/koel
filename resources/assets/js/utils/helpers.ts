import { isObject } from 'lodash'
import { inject, InjectionKey, isRef, provide, readonly, shallowReadonly } from 'vue'
import { ReadonlyInjectionKey } from '@/symbols'
import { logger } from '@/utils'

export const use = <T> (value: T, cb: (arg: T) => void) => {
  if (typeof value === 'undefined' || value === null) {
    return
  }

  cb(value)
}

export const arrayify = <T> (maybeArray: T | Array<T>) => ([] as Array<T>).concat(maybeArray)

// @ts-ignore
export const noop = () => {
}

export const limitBy = <T> (arr: T[], count: number, offset: number = 0): T[] => arr.slice(offset, offset + count)

export const provideReadonly = <T> (key: ReadonlyInjectionKey<T>, value: T, deep = true, mutator?: Closure) => {
  mutator = mutator || (v => isRef(value) ? (value.value = v) : (value = v))

  if (!isObject(value)) {
    logger.warn(`value cannot be made readonly: ${value}`)
    provide(key, [value, mutator])
  } else {
    provide(key, [deep ? readonly(value) : shallowReadonly(value), mutator])
  }
}

export const requireInjection = <T> (key: InjectionKey<T>, defaultValue?: T) => {
  const value = inject(key, defaultValue)

  if (typeof value === 'undefined') {
    throw new Error(`Missing injection: ${key.toString()}`)
  }

  return value
}
