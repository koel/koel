import select from 'select'
import { arrayify, eventBus, noop, pluralize } from '@/utils'
import defaultCover from '@/../img/covers/unknown-album.png'

export { defaultCover }

/**
 * Load (display) a main panel (view).
 *
 * @param view
 * @param {...*} args     Extra data to attach to the view.
 */
export const loadMainView = (view: MainViewName, ...args: any[]): void => {
  eventBus.emit('LOAD_MAIN_CONTENT', view, ...args)
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

export const showOverlay = (message = 'Just a little patience…', type = 'loading', dismissible = false) => {
  eventBus.emit('SHOW_OVERLAY', { message, type, dismissible })
}

export const hideOverlay = (): void => {
  eventBus.emit('HIDE_OVERLAY')
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

const createGhostDragImage = (event: DragEvent, text: string): void => {
  if (!event.dataTransfer) {
    return
  }

  let dragGhost = document.querySelector<HTMLElement>('#dragGhost')

  if (!dragGhost) {
    // Create the element to be the ghost drag image.
    dragGhost = document.createElement('div')
    dragGhost.id = 'dragGhost'
    document.body.appendChild(dragGhost)
  }

  dragGhost.innerText = text
  event.dataTransfer.setDragImage(dragGhost, 0, 0)
}

/**
 * Handle song/album/artist drag start event.
 */
export const startDragging = (event: DragEvent, dragged: Song | Song[] | Album | Artist, type: DragType): void => {
  if (!event.dataTransfer) {
    return
  }

  let text
  let songIds

  switch (type) {
    case 'Song':
      dragged = arrayify(<Song>dragged)
      text = dragged.length === 1
        ? `${dragged[0].title} by ${dragged[0].artist.name}`
        : pluralize(dragged.length, 'song')
      songIds = dragged.map(song => song.id)
      break

    case 'Album':
      dragged = <Album>dragged
      text = `All ${pluralize(dragged.songs.length, 'song')} in ${dragged.name}`
      songIds = dragged.songs.map(song => song.id)
      break

    case 'Artist':
      dragged = <Artist>dragged
      text = `All ${pluralize(dragged.songs.length, 'song')} by ${dragged.name}`
      songIds = dragged.songs.map(song => song.id)
      break

    default:
      throw Error(`Invalid drag type: ${type}`)
  }

  event.dataTransfer.setData('application/x-koel.text+plain', songIds.join(','))
  event.dataTransfer.effectAllowed = 'move'

  createGhostDragImage(event, text)
}

export const isDemo = KOEL_ENV === 'demo'
