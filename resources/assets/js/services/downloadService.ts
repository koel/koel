import { favoriteStore, playlistStore } from '@/stores'
import { authService } from '.'
import { alerts, arrayify } from '@/utils'

export const downloadService = {
  fromSongs (songs: Song | Song[]): void {
    const query = arrayify(songs).reduce((q, song) => `songs[]=${song.id}&${q}`, '')
    this.trigger(`songs?${query}`)
  },

  fromAlbum (album: Album): void {
    this.trigger(`album/${album.id}`)
  },

  fromArtist (artist: Artist): void {
    this.trigger(`artist/${artist.id}`)
  },

  fromPlaylist (playlist: Playlist): void {
    if (playlistStore.getSongs(playlist).length) {
      this.trigger(`playlist/${playlist.id}`)
    }
  },

  fromFavorites (): void {
    if (favoriteStore.all.length) {
      this.trigger('favorites')
    }
  },

  /**
   * Build a download link using a segment and trigger it.
   *
   * @param  {string} uri The uri segment, corresponding to the song(s),
   *                      artist, playlist, or album.
   */
  trigger: (uri: string) => {
    const sep = uri.includes('?') ? '&' : '?'
    const url = `${window.BASE_URL}download/${uri}${sep}api_token=${authService.getToken()}`

    if (KOEL_ENV === 'app') {
      require('electron').ipcRenderer.send('DOWNLOAD', url)
      alerts.success('Download started!')
    } else {
      const iframe = document.createElement('iframe')
      iframe.style.display = 'none'
      iframe.setAttribute('src', url)
      document.body.appendChild(iframe)
    }
  }
}
