import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { reactive } from 'vue'
import factory from '@/__tests__/factory'
import { http } from '@/services'
import { albumStore, artistStore, ExcerptSearchResult, searchStore, songStore } from '.'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      searchStore.state = reactive({
        excerpt: {
          playables: [],
          albums: [],
          artists: []
        },
        playables: []
      })
    })
  }

  protected test () {
    it('performs an excerpt search', async () => {
      const result: ExcerptSearchResult = {
        playables: factory('song', 3),
        albums: factory('album', 3),
        artists: factory('artist', 3)
      }

      const getMock = this.mock(http, 'get').mockResolvedValue(result)
      const syncSongsMock = this.mock(songStore, 'syncWithVault', result.songs)
      const syncAlbumsMock = this.mock(albumStore, 'syncWithVault', result.albums)
      const syncArtistsMock = this.mock(artistStore, 'syncWithVault', result.artists)

      await searchStore.excerptSearch('test')

      expect(getMock).toHaveBeenCalledWith('search?q=test')
      expect(syncSongsMock).toHaveBeenCalledWith(result.songs)
      expect(syncAlbumsMock).toHaveBeenCalledWith(result.albums)
      expect(syncArtistsMock).toHaveBeenCalledWith(result.artists)

      expect(searchStore.state.excerpt.songs).toEqual(result.songs)
      expect(searchStore.state.excerpt.albums).toEqual(result.albums)
      expect(searchStore.state.excerpt.artists).toEqual(result.artists)
    })

    it('performs a song search', async () => {
      const songs = factory('song', 3)

      const getMock = this.mock(http, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await searchStore.playableSearch('test')

      expect(getMock).toHaveBeenCalledWith('search/songs?q=test')
      expect(syncMock).toHaveBeenCalledWith(songs)

      expect(searchStore.state.playables).toEqual(songs)
    })

    it('resets the song result state', () => {
      searchStore.state.playables = factory('song', 3)
      searchStore.resetPlayableResultState()
      expect(searchStore.state.playables).toEqual([])
    })
  }
}
