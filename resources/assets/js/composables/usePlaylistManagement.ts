import { playlistStore } from '@/stores'
import { eventBus, logger, pluralize, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

export const usePlaylistManagement = () => {
  const dialog = requireInjection(DialogBoxKey)
  const toaster = requireInjection(MessageToasterKey)

  const addSongsToPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart || songs.length === 0) return

    try {
      await playlistStore.addSongs(playlist, songs)
      eventBus.emit('PLAYLIST_UPDATED', playlist)
      toaster.value.success(`Added ${pluralize(songs, 'song')} into "${playlist.name}."`)
    } catch (error) {
      logger.error(error)
      dialog.value.error('Something went wrong. Please try again.', 'Error')
    }
  }

  const removeSongsFromPlaylist = async (playlist: Playlist, songs: Song[]) => {
    if (playlist.is_smart) return

    try {
      await playlistStore.removeSongs(playlist, songs)
      eventBus.emit('PLAYLIST_SONGS_REMOVED', playlist, songs)
      toaster.value.success(`Removed ${pluralize(songs, 'song')} from "${playlist.name}."`)
    } catch (error) {
      logger.error(error)
      dialog.value.error('Something went wrong. Please try again.', 'Error')
    }
  }

  return {
    addSongsToPlaylist,
    removeSongsFromPlaylist
  }
}
