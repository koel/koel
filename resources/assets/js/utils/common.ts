import select from 'select'
import { noop } from '@/utils'
import defaultCover from '@/../img/covers/default.svg'

export { defaultCover }

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

export const copyText = (text: string): void => {
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

export const isDemo = () => {
  // can't use one-liner as it would break production build with an "Unexpected token" error
  return import.meta.env.VITE_KOEL_ENV === 'demo'
}
