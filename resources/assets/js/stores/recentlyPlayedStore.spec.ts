import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import { songStore } from '@/stores/songStore'

new class extends UnitTestCase {
  protected test () {
    it('fetches the recently played songs', async () => {
      const songs = factory('song', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await recentlyPlayedStore.fetch()

      expect(getMock).toHaveBeenCalledWith('songs/recently-played')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(recentlyPlayedStore.state.playables).toEqual(songs)
    })

    it('fetches when attempting to add a new song and the state is empty', async () => {
      recentlyPlayedStore.state.playables = []
      const songs = factory('song', 3)
      const fetchMock = this.mock(recentlyPlayedStore, 'fetch').mockResolvedValue(songs)

      await recentlyPlayedStore.add(factory('song'))

      expect(fetchMock).toHaveBeenCalled()
    })

    it('adds a song to the state', async () => {
      const newSong = factory('song')
      const songs = factory('song', 10)
      const exceptSongs = songs.slice(0, 7)

      // We don't want to keep the reference to the original songs
      recentlyPlayedStore.state.playables = JSON.parse(JSON.stringify(songs))
      recentlyPlayedStore.excerptState.playables = JSON.parse(JSON.stringify(exceptSongs))

      await recentlyPlayedStore.add(newSong)

      expect(recentlyPlayedStore.state.playables).toEqual([newSong, ...songs])
      expect(recentlyPlayedStore.excerptState.playables).toEqual([newSong, ...songs.slice(0, 6)])
    })

    it('deduplicates when adding a song to the state', async () => {
      const songs = factory('song', 10)
      const newSong = songs[1]
      const exceptSongs = songs.slice(0, 7)

      // We don't want to keep the reference to the original songs
      recentlyPlayedStore.state.playables = JSON.parse(JSON.stringify(songs))
      recentlyPlayedStore.excerptState.playables = JSON.parse(JSON.stringify(exceptSongs))

      await recentlyPlayedStore.add(newSong)

      expect(recentlyPlayedStore.state.playables).toEqual([newSong, songs[0], ...songs.slice(2)])
      expect(recentlyPlayedStore.excerptState.playables).toEqual([newSong, songs[0], ...songs.slice(2, 7)])
    })
  }
}
