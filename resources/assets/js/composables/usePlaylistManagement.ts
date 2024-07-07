import { playlistStore } from '@/stores'
import { eventBus, getPlayableCollectionContentType } from '@/utils'
import { useErrorHandler, useMessageToaster } from '@/composables'

export const usePlaylistManagement = () => {
  const { handleHttpError } = useErrorHandler('dialog')
  const { toastSuccess } = useMessageToaster()

  const inflect = (playables: Playable[]) => {
    switch (getPlayableCollectionContentType(playables)) {
      case 'songs':
        return playables.length === 1 ? 'Song' : 'Songs'
      case 'episodes':
        return playables.length === 1 ? 'Episode' : 'Episodes'
      default:
        return playables.length === 1 ? 'Item' : 'Items'
    }
  }

  const addToPlaylist = async (playlist: Playlist, playables: Playable[]) => {
    if (playlist.is_smart || playables.length === 0) return

    try {
      await playlistStore.addContent(playlist, playables)
      eventBus.emit('PLAYLIST_UPDATED', playlist)
      toastSuccess(`${inflect(playables)} added into "${playlist.name}."`)
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  const removeFromPlaylist = async (playlist: Playlist, playables: Playable[]) => {
    if (playlist.is_smart) return

    try {
      await playlistStore.removeContent(playlist, playables)
      eventBus.emit('PLAYLIST_CONTENT_REMOVED', playlist, playables)
      toastSuccess(`${inflect(playables)} removed from "${playlist.name}."`)
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  return {
    addToPlaylist,
    removeFromPlaylist
  }
}
