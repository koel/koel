import { useI18n } from 'vue-i18n'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { getPlayableCollectionContentType } from '@/utils/typeGuards'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useMessageToaster } from '@/composables/useMessageToaster'

export const usePlaylistContentManagement = () => {
  const { t } = useI18n()
  const { handleHttpError } = useErrorHandler('dialog')
  const { toastSuccess } = useMessageToaster()

  const inflect = (playables: Playable[]) => {
    const contentType = getPlayableCollectionContentType(playables)
    const isSingular = playables.length === 1

    switch (contentType) {
      case 'songs':
        return isSingular ? t('messages.itemsSingular') : t('messages.itemsPlural')
      case 'episodes':
        return isSingular ? t('messages.episodesSingular') : t('messages.episodesPlural')
      default:
        return isSingular ? t('messages.genericItemSingular') : t('messages.genericItemPlural')
    }
  }

  const addToPlaylist = async (playlist: Playlist, playables: Playable[]) => {
    if (playlist.is_smart || playables.length === 0) {
      return
    }

    try {
      await playlistStore.addContent(playlist, playables)
      eventBus.emit('PLAYLIST_UPDATED', playlist)
      toastSuccess(t('messages.itemsAdded', { count: playables.length, item: inflect(playables), playlist: playlist.name }))
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  const removeFromPlaylist = async (playlist: Playlist, playables: Playable[]) => {
    if (playlist.is_smart) {
      return
    }

    try {
      await playlistStore.removeContent(playlist, playables)
      eventBus.emit('PLAYLIST_CONTENT_REMOVED', playlist, playables)
      toastSuccess(t('messages.itemsRemoved', { count: playables.length, item: inflect(playables), playlist: playlist.name }))
    } catch (error: unknown) {
      handleHttpError(error)
    }
  }

  return {
    addToPlaylist,
    removeFromPlaylist,
  }
}
