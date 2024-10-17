import { pluralize } from '@/utils/formatters'
import { arrayify, getPlayableProp } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playlistStore } from '@/stores/playlistStore'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { songStore } from '@/stores/songStore'

type Draggable = MaybeArray<Playable> | Album | Artist | Playlist | PlaylistFolder
const draggableTypes = <const>['playables', 'album', 'artist', 'playlist', 'playlist-folder']
type DraggableType = typeof draggableTypes[number]

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

  dragGhost.textContent = text
  event.dataTransfer.setDragImage(dragGhost, 0, 0)
}

const getDragType = (event: DragEvent) => {
  return draggableTypes.find(type => event.dataTransfer?.types.includes(`application/x-koel.${type}`))
}

export const useDraggable = (type: DraggableType) => {
  const startDragging = (event: DragEvent, dragged: Draggable) => {
    if (!event.dataTransfer) {
      return
    }

    event.dataTransfer.effectAllowed = 'copyMove'

    let text: string
    let data: any

    switch (type) {
      case 'playables':
        dragged = arrayify(<Playable>dragged)
        text = dragged.length === 1
          ? `${dragged[0].title} by ${getPlayableProp(dragged[0], 'artist_name', 'podcast_author')}`
          : pluralize(dragged, 'item')

        data = dragged.map(song => song.id)
        break

      case 'album':
        dragged = <Album>dragged
        text = `All songs in ${dragged.name}`
        data = dragged.id
        break

      case 'artist':
        dragged = <Artist>dragged
        text = `All songs by ${dragged.name}`
        data = dragged.id
        break

      case 'playlist':
        dragged = <Playlist>dragged
        text = dragged.name
        data = dragged.id
        break

      case 'playlist-folder':
        dragged = <PlaylistFolder>dragged
        text = dragged.name
        data = dragged.id
        break

      default:
        return
    }

    event.dataTransfer.setData(`application/x-koel.${type}`, JSON.stringify(data))

    createGhostDragImage(event, text)
  }

  return {
    startDragging,
  }
}

export const useDroppable = (acceptedTypes: DraggableType[]) => {
  const acceptsDrop = (event: DragEvent) => {
    const type = getDragType(event)
    return Boolean(type && acceptedTypes.includes(type))
  }

  const getDroppedData = (event: DragEvent) => {
    const type = getDragType(event)

    if (!type) {
      return null
    }

    try {
      return JSON.parse(event.dataTransfer!.getData(`application/x-koel.${type}`)!)
    } catch (error: unknown) {
      logger.warn('Failed to parse dropped data', error)
      return null
    }
  }

  const resolveDroppedValue = async <T = Playlist> (event: DragEvent): Promise<T | undefined> => {
    try {
      switch (getDragType(event)) {
        case 'playlist':
          const id = String(JSON.parse(event.dataTransfer!.getData('application/x-koel.playlist')))
          return playlistStore.byId(id) as T | undefined
        default:
      }
    } catch (error: unknown) {
      logger.error(error, event)
    }
  }

  const resolveDroppedItems = async (event: DragEvent) => {
    try {
      const type = getDragType(event)
      if (!type) {
        return <Playable[]>[]
      }

      const data = getDroppedData(event)
      switch (type) {
        case 'playables':
          return songStore.byIds(<string[]>data)
        case 'album':
          const album = await albumStore.resolve(<number>data)
          return album ? await songStore.fetchForAlbum(album) : <Song[]>[]
        case 'artist':
          const artist = await artistStore.resolve(<number>data)
          return artist ? await songStore.fetchForArtist(artist) : <Song[]>[]
        case 'playlist':
          const playlist = playlistStore.byId(<string>data)
          return playlist ? await songStore.fetchForPlaylist(playlist) : <Song[]>[]
        case 'playlist-folder':
          const folder = playlistFolderStore.byId(<string>data)
          return folder ? await songStore.fetchForPlaylistFolder(folder) : <Song[]>[]
        default:
          throw new Error(`Unknown drag type: ${type}`)
      }
    } catch (error: unknown) {
      logger.error(error, event)
      return <Song[]>[]
    }
  }

  return {
    acceptsDrop,
    getDroppedData,
    resolveDroppedValue,
    resolveDroppedItems,
  }
}
