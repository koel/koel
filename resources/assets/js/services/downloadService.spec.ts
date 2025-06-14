import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { favoriteStore } from '@/stores/favoriteStore'
import { downloadService } from './downloadService'

new class extends UnitTestCase {
  protected test () {
    it('downloads playables', () => {
      const mock = this.mock(downloadService, 'trigger')
      downloadService.fromPlayables([factory('song', { id: 'bar' })])

      expect(mock).toHaveBeenCalledWith('songs?songs[]=bar&')
    })

    it('downloads all by artist', () => {
      const mock = this.mock(downloadService, 'trigger')
      const artist = factory('artist')
      downloadService.fromArtist(artist)

      expect(mock).toHaveBeenCalledWith(`artist/${artist.id}`)
    })

    it('downloads all in album', () => {
      const mock = this.mock(downloadService, 'trigger')
      const album = factory('album')
      downloadService.fromAlbum(album)

      expect(mock).toHaveBeenCalledWith(`album/${album.id}`)
    })

    it('downloads a playlist', () => {
      const mock = this.mock(downloadService, 'trigger')
      const playlist = factory('playlist')

      downloadService.fromPlaylist(playlist)

      expect(mock).toHaveBeenCalledWith(`playlist/${playlist.id}`)
    })

    it.each<[Playable[], boolean]>([[[], false], [factory('song', 5), true]])(
      'downloads favorites if available',
      (songs, triggered) => {
        const mock = this.mock(downloadService, 'trigger')
        favoriteStore.state.playables = songs

        downloadService.fromFavorites()

        triggered ? expect(mock).toHaveBeenCalledWith('favorites') : expect(mock).not.toHaveBeenCalled()
      },
    )
  }
}
