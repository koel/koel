export class DownloadLimitExceededError extends Error {}

import { isHttpError } from '@/services/http'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { arrayify, flattenParams } from '@/utils/helpers'

export const downloadService = {
  async fromPlayables(playables: MaybeArray<Playable>) {
    const items = arrayify(playables)

    if (items.length === 1) {
      this.trigger(`songs?songs[]=${items[0].id}`)
      return
    }

    await this.checkDownloadable({ type: 'songs', ids: items.map(p => p.id) })

    const query = items.reduce((q, playable) => `songs[]=${playable.id}&${q}`, '')
    this.trigger(`songs?${query}`)
  },

  async fromAlbum(album: Album) {
    await this.checkDownloadable({ type: 'album', id: album.id })
    this.trigger(`album/${album.id}`)
  },

  async fromArtist(artist: Artist) {
    await this.checkDownloadable({ type: 'artist', id: artist.id })
    this.trigger(`artist/${artist.id}`)
  },

  async fromPlaylist(playlist: Playlist) {
    await this.checkDownloadable({ type: 'playlist', id: playlist.id })
    this.trigger(`playlist/${playlist.id}`)
  },

  async fromFavorites() {
    if (!playableStore.state.favorites.length) {
      return
    }

    await this.checkDownloadable({ type: 'favorites' })
    this.trigger('favorites')
  },

  /**
   * @throws {DownloadLimitExceededError} if the server rejects the download due to limit
   */
  async checkDownloadable(params: Record<string, unknown>) {
    try {
      await http.get<void>(`download/check?${new URLSearchParams(flattenParams(params))}`)
    } catch (error: unknown) {
      if (isHttpError(error) && error.response?.status === 403) {
        throw new DownloadLimitExceededError((error as any).responseData?.message)
      }

      throw error
    }
  },

  trigger: (uri: string) => {
    const sep = uri.includes('?') ? '&' : '?'
    const url = `${window.KOEL.base_url}download/${uri}${sep}t=${authService.getAudioToken()}`

    open(url)
  },
}
