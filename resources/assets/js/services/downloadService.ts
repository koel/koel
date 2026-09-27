import { authService } from '@/services/authService'
import { zipDownloadService } from '@/services/zipDownloadService'
import { playableStore } from '@/stores/playableStore'
import { arrayify } from '@/utils/helpers'

export const downloadService = {
  async fromPlayables(playables: MaybeArray<Playable>) {
    const items = arrayify(playables)

    if (items.length === 1) {
      this.trigger(items[0])
      return
    }

    await zipDownloadService.start(items, 'koel-download', 'none')
  },

  async fromAlbum(album: Album) {
    await zipDownloadService.start(await playableStore.fetchSongsForAlbum(album), album.name, 'track')
  },

  async fromArtist(artist: Artist) {
    await zipDownloadService.start(await playableStore.fetchSongsForArtist(artist), artist.name, 'none')
  },

  async fromPlaylist(playlist: Playlist) {
    await zipDownloadService.start(await playableStore.fetchForPlaylist(playlist), playlist.name, 'position')
  },

  async fromFavorites() {
    if (!playableStore.state.favorites.length) {
      return
    }

    await zipDownloadService.start(playableStore.state.favorites, 'Favorites', 'none')
  },

  trigger: (playable: Playable) => {
    open(`${window.KOEL.base_url}download/songs?songs[]=${playable.id}&t=${authService.getAudioToken()}`)
  },
}
