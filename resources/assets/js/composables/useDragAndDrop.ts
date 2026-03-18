import { pluralize } from '@/utils/formatters'
import { arrayify, getPlayableProp } from '@/utils/helpers'
import { logger } from '@/utils/logger'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playlistStore } from '@/stores/playlistStore'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playableStore } from '@/stores/playableStore'
import { mediaBrowser } from '@/services/mediaBrowser'

type Draggable = MaybeArray<Playable> | Album | Artist | Genre | Playlist | PlaylistFolder | MaybeArray<Song | Folder>
const draggableTypes = <const>['playables', 'album', 'artist', 'genre', 'playlist', 'playlist-folder', 'browser-media']
type DraggableType = (typeof draggableTypes)[number]

// A transparent 1x1 image used to suppress the browser's default drag ghost.
const emptyDragImage = (() => {
  const img = new Image()
  img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
  return img
})()

const createGhostDragImage = (event: DragEvent, text: string): void => {
  if (!event.dataTransfer) {
    return
  }

  let dragGhost = document.querySelector<HTMLElement>('#dragGhost')

  if (!dragGhost) {
    dragGhost = document.createElement('div')
    dragGhost.id = 'dragGhost'
    dragGhost.setAttribute('aria-hidden', 'true')
    document.body.appendChild(dragGhost)
  }

  dragGhost.textContent = text

  // Use a transparent image as the native drag ghost to avoid browser rendering artifacts.
  // The real ghost is a custom DOM element that follows the cursor via the 'drag' event.
  event.dataTransfer.setDragImage(emptyDragImage, 0, 0)

  dragGhost.style.display = 'block'

  let rafId: number | null = null

  const cleanup = () => {
    dragGhost!.style.display = 'none'

    if (rafId !== null) {
      cancelAnimationFrame(rafId)
      rafId = null
    }

    document.removeEventListener('drag', onDrag)
    document.removeEventListener('dragend', cleanup)
    document.removeEventListener('drop', cleanup, true)
  }

  const onDrag = (e: DragEvent) => {
    if (e.clientX === 0 && e.clientY === 0) {
      // Browser sends (0,0) as the final drag event — hide the ghost immediately.
      cleanup()
      return
    }

    if (rafId !== null) {
      cancelAnimationFrame(rafId)
    }

    rafId = requestAnimationFrame(() => {
      dragGhost!.style.left = `${e.clientX}px`
      dragGhost!.style.top = `${e.clientY}px`
      rafId = null
    })
  }

  document.addEventListener('drag', onDrag)
  document.addEventListener('dragend', cleanup)
  document.addEventListener('drop', cleanup, true)
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
        text =
          dragged.length === 1
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

      case 'browser-media':
        dragged = arrayify(dragged as MaybeArray<Song | Folder>)
        data = mediaBrowser.extractMediaReferences(dragged)
        text = pluralize(dragged, 'item')
        break

      case 'genre':
        dragged = <Genre>dragged
        data = dragged.id
        text = dragged.name || 'No Genre'
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

  const resolveDroppedValue = async <T = Playlist>(event: DragEvent): Promise<T | undefined> => {
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
          return playableStore.byIds(<string[]>data)
        case 'album':
          const album = await albumStore.resolve(data)
          return album ? await playableStore.fetchSongsForAlbum(album) : <Song[]>[]
        case 'artist':
          const artist = await artistStore.resolve(data)
          return artist ? await playableStore.fetchSongsForArtist(artist) : <Song[]>[]
        case 'playlist':
          const playlist = playlistStore.byId(<string>data)
          return playlist ? await playableStore.fetchForPlaylist(playlist) : <Song[]>[]
        case 'playlist-folder':
          const folder = playlistFolderStore.byId(<string>data)
          return folder ? await playableStore.fetchForPlaylistFolder(folder) : <Song[]>[]
        case 'browser-media':
          return await playableStore.resolveSongsFromMediaReferences(data)
        case 'genre':
          return await playableStore.fetchSongsByGenre(<string>data)
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
