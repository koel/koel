import { arrayify } from '@/utils/helpers'
import { pluralize } from '@/utils/formatters'
import { albumStore, artistStore, songStore } from '@/stores'

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

const startDragging = (event: DragEvent, dragged: Song | Song[] | Album | Artist, type: DragType): void => {
  if (!event.dataTransfer) {
    return
  }

  let text
  let data: DragData

  switch (type) {
    case 'Song':
      dragged = arrayify(dragged as Song)
      text = dragged.length === 1
        ? `${dragged[0].title} by ${dragged[0].artist_name}`
        : pluralize(dragged.length, 'song')

      data = {
        type: 'songs',
        value: dragged.map(song => song.id)
      }

      break

    case 'Album':
      dragged = dragged as Album
      text = `All songs in ${dragged.name}`

      data = {
        type: 'album',
        value: dragged.id
      }

      break

    case 'Artist':
      dragged = dragged as Artist
      text = `All songs by ${dragged.name}`

      data = {
        type: 'artist',
        value: dragged.id
      }

      break

    default:
      throw Error(`Invalid drag type: ${type}`)
  }

  event.dataTransfer.setData('application/x-koel.text+plain', JSON.stringify(data))
  event.dataTransfer.effectAllowed = 'move'

  createGhostDragImage(event, text)
}

const resolveSongsFromDragEvent = async (event: DragEvent) => {
  if (!event.dataTransfer?.getData('application/x-koel.text+plain')) {
    return []
  }

  const data: DragData = JSON.parse(event.dataTransfer.getData('application/x-koel.text+plain'))

  switch (data.type) {
    case 'songs':
      return songStore.byIds(data.value as string[])
    case 'album':
      return await songStore.fetchForAlbum(await albumStore.resolve(data.value as number))
    case 'artist':
      return await songStore.fetchForArtist(await artistStore.resolve(data.value as number))
    default:
      console.warn('Unhandled drag data type', data.type)
      return []
  }
}

export {
  startDragging,
  resolveSongsFromDragEvent
}
