import select from 'select'
import { OverlayState } from 'koel/types/ui'
import { eventBus, noop } from '@/utils'
import defaultCover from '@/../img/covers/unknown-album.png'

export { defaultCover }

/**
 * Load (display) a main panel (view).
 *
 * @param view
 * @param {...*} args     Extra data to attach to the view.
 */
export const loadMainView = (view: MainViewName, ...args: any[]) => eventBus.emit('LOAD_MAIN_CONTENT', view, ...args)

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

export const showOverlay = (
  message = 'Just a little patience…',
  type: OverlayState['type'] = 'loading',
  dismissible = false
) => eventBus.emit('SHOW_OVERLAY', { message, type, dismissible })

export const hideOverlay = () => eventBus.emit('HIDE_OVERLAY')

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

export const isDemo = KOEL_ENV === 'demo'
