import { playlistStore } from '@/stores'
import { eventBus, pluralize } from '@/utils'
import { useErrorHandler, useMessageToaster } from '@/composables'

export const usePlaylistManagement = () => {
  const { handleHttpError } = useErrorHandler('dialog')
  const { toastSuccess } = useMessageToaster()

  const addSongsToPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart || songs.length === 0) return

    try {
      await playlistStore.addSongs(playlist, songs)
      eventBus.emit('PLAYLIST_UPDATED', playlist)
      toastSuccess(`Added ${pluralize(songs, 'song')} into "${playlist.name}."`)
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  const removeSongsFromPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) return

    try {
      await playlistStore.removeSongs(playlist, songs)
      eventBus.emit('PLAYLIST_SONGS_REMOVED', playlist, songs)
      toastSuccess(`Removed ${pluralize(songs, 'song')} from "${playlist.name}."`)
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  return {
    addSongsToPlaylist,
    removeSongsFromPlaylist
  }
}
