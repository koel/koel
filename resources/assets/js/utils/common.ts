import select from 'select'
import { isSong, noop } from '@/utils'
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

export const copyText = async (text: string) => {
  try {
    await navigator.clipboard.writeText(text)
  } catch (error: unknown) {
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

export const getPlayableProp = <T>(playable: Playable, songKey: keyof Song, episodeKey: keyof Episode): T => {
  return isSong(playable) ? playable[songKey] : playable[episodeKey]
}
