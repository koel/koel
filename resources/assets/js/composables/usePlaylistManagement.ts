import { playlistStore } from '@/stores'
import { eventBus, logger, pluralize } from '@/utils'
import { useDialogBox, useMessageToaster } from '@/composables'

export const usePlaylistManagement = () => {
  const { toastSuccess } = useMessageToaster()
  const { showErrorDialog } = useDialogBox()

  const addSongsToPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart || songs.length === 0) return

    try {
      await playlistStore.addSongs(playlist, songs)
      eventBus.emit('PLAYLIST_UPDATED', playlist)
      toastSuccess(`Added ${pluralize(songs, 'song')} into "${playlist.name}."`)
    } catch (error) {
      logger.error(error)
      showErrorDialog('Something went wrong. Please try again.', 'Error')
    }
  }

  const removeSongsFromPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) return

    try {
      await playlistStore.removeSongs(playlist, songs)
      eventBus.emit('PLAYLIST_SONGS_REMOVED', playlist, songs)
      toastSuccess(`Removed ${pluralize(songs, 'song')} from "${playlist.name}."`)
    } catch (error) {
      logger.error(error)
      showErrorDialog('Something went wrong. Please try again.', 'Error')
    }
  }

  return {
    addSongsToPlaylist,
    removeSongsFromPlaylist
  }
}
