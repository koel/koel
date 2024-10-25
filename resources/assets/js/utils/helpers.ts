import select from 'select'
import { isObject, without } from 'lodash'
import type { AsyncComponentLoader, Component, InjectionKey } from 'vue'
import { defineAsyncComponent as baseDefineAsyncComponent, inject, isRef, provide, readonly, shallowReadonly } from 'vue'
import type { ReadonlyInjectionKey } from '@/symbols'
import { logger } from '@/utils/logger'
import { md5 } from '@/utils/crypto'
import { isSong } from '@/utils/typeGuards'

import LoadingComponent from '@/components/ui/skeletons/Loading.vue'

export const use = <T> (value: T | undefined | null, cb: (arg: T) => void) => {
  if (typeof value === 'undefined' || value === null) {
    return
  }

  cb(value)
}

export const arrayify = <T> (maybeArray: MaybeArray<T>) => Array.isArray(maybeArray) ? maybeArray : [maybeArray]

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
    throw new TypeError(`Missing injection: ${key.toString()}`)
  }

  return value
}

export const moveItemsInList = <T> (list: T[], items: T | T[], target: T, type: MoveType) => {
  if (!list.includes(target)) {
    throw new Error('Target not found in list')
  }

  const subset = arrayify(items)

  const isTargetAdjacent = type === 'before'
    ? list.indexOf(subset[subset.length - 1]) + 1 === list.indexOf(target)
    : list.indexOf(subset[0]) - 1 === list.indexOf(target)

  if (isTargetAdjacent) {
    return list
  }

  const updatedList = without(list, ...subset)
  const targetIndex = updatedList.indexOf(target)
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
/**
 * Force reloading window regardless of "Confirm before reload" setting.
 * This is handy for certain cases, for example Last.fm connect/disconnect.
 */
export const forceReloadWindow = (): void => {
  if (process.env.NODE_ENV === 'test') {
    return
  }

  window.onbeforeunload = noop
  window.location.reload()
}

export const copyText = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
  } catch (error: unknown) {
    logger.warn('Failed to copy text to clipboard using navigator.clipboard.writeText()', error)

    let copyArea = document.querySelector<HTMLTextAreaElement>('#copyArea')

    if (!copyArea) {
      copyArea = document.createElement('textarea')
      copyArea.id = 'copyArea'
      document.body.appendChild(copyArea)
    }

    copyArea.style.top = `${window.scrollY || document.documentElement.scrollTop}px`
    copyArea.value = text
    select(copyArea)
    document.execCommand('copy')
  }
}

export const getPlayableProp = <T> (playable: Playable, songKey: keyof Song, episodeKey: keyof Episode): T => {
  return isSong(playable) ? playable[songKey] : playable[episodeKey]
}

export const defineAsyncComponent = (loader: AsyncComponentLoader, loadingComponent?: Component) => {
  return baseDefineAsyncComponent({
    loader,
    loadingComponent: loadingComponent || LoadingComponent,
  })
}
