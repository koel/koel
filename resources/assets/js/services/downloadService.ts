export class DownloadLimitExceededError extends Error {}

import { getHttpErrorBody, isHttpError } from '@/services/http'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { zipDownloadService } from '@/services/zipDownloadService'
import { playableStore } from '@/stores/playableStore'
import { arrayify, flattenParams } from '@/utils/helpers'

export const downloadService = {
  async fromPlayables(playables: MaybeArray<Playable>) {
    const items = arrayify(playables)

    if (items.length === 1) {
      this.trigger(items[0])
      return
    }

    await this.checkDownloadable({ type: 'songs', ids: items.map(p => p.id) })
    await zipDownloadService.start(items, 'Songs', 'none')
  },

  async fromAlbum(album: Album) {
    await this.checkDownloadable({ type: 'album', id: album.id })
    await zipDownloadService.start(await playableStore.fetchSongsForAlbum(album), album.name, 'track')
  },

  async fromArtist(artist: Artist) {
    await this.checkDownloadable({ type: 'artist', id: artist.id })
    await zipDownloadService.start(await playableStore.fetchSongsForArtist(artist), artist.name, 'none')
  },

  async fromPlaylist(playlist: Playlist) {
    await this.checkDownloadable({ type: 'playlist', id: playlist.id })
    await zipDownloadService.start(await playableStore.fetchForPlaylist(playlist), playlist.name, 'position')
  },

  async fromFavorites() {
    if (!playableStore.state.favorites.length) {
      return
    }

    await this.checkDownloadable({ type: 'favorites' })
    await zipDownloadService.start(playableStore.state.favorites, 'Favorites', 'none')
  },

  /**
   * @throws {DownloadLimitExceededError} if the server rejects the download due to limit
   */
  async checkDownloadable(params: Record<string, unknown>) {
    try {
      await http.get<void>(`download/check?${new URLSearchParams(flattenParams(params))}`)
    } catch (error: unknown) {
      if (isHttpError(error) && error.response?.status === 403) {
        throw new DownloadLimitExceededError(getHttpErrorBody(error)?.message)
      }

      throw error
    }
  },

  trigger: (playable: Playable) => {
    open(`${window.KOEL.base_url}download/songs?songs[]=${playable.id}&t=${authService.getAudioToken()}`)
  },
}
