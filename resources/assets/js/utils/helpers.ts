import { isObject, without } from 'lodash'
import { inject, InjectionKey, isRef, provide, readonly, shallowReadonly } from 'vue'
import { ReadonlyInjectionKey } from '@/symbols'
import { logger, md5 } from '@/utils'

export const use = <T> (value: T, cb: (arg: T) => void) => {
  if (typeof value === 'undefined' || value === null) {
    return
  }

  cb(value)
}

export const arrayify = <T> (maybeArray: T | Array<T>) => Array.isArray(maybeArray) ? maybeArray : [maybeArray]

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

export const dbToGain = (db: number) => Math.pow(10, db / 20) || 0

export const moveItemsInList = <T> (list: T[], items: T | T[], target: T, type: MoveType) => {
  if (list.indexOf(target) === -1) {
    throw 'Target not found in list'
  }

  const subset = arrayify(items)

  const isTargetAdjacent = type === 'before'
    ? list.indexOf(subset[subset.length - 1]) + 1 === list.indexOf(target)
    : list.indexOf(subset[0]) - 1 === list.indexOf(target)

  if (isTargetAdjacent) {
    return list
  }

  const updatedList = without(list, ...subset)
  const targetIndex = updatedList.indexOf(target);
  updatedList.splice(type === 'before' ? targetIndex : targetIndex + 1, 0, ...subset)

  return updatedList
}

export const gravatar = (email: string, size = 192) => {
  const hash = md5(email.trim().toLowerCase())
  return `https://www.gravatar.com/avatar/${hash}?s=${size}&d=robohash`
}

export const openPopup = (url: string, name: string, width: number, height: number, parent: Window) => {
  const y = parent.top!.outerHeight / 2 + parent.top!.screenY - (height / 2)
  const x = parent.top!.outerWidth / 2 + parent.top!.screenX - (width / 2)
  return parent.open(url, name, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${width}, height=${height}, top=${y}, left=${x}`)
}
