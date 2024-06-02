import { favoriteStore } from '@/stores'
import factory from '@/__tests__/factory'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
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
      downloadService.fromArtist(factory('artist', { id: 42 }))

      expect(mock).toHaveBeenCalledWith('artist/42')
    })

    it('downloads all in album', () => {
      const mock = this.mock(downloadService, 'trigger')
      downloadService.fromAlbum(factory('album', { id: 42 }))

      expect(mock).toHaveBeenCalledWith('album/42')
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
      })
  }
}
