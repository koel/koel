import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { songStore } from '@/stores/songStore'
import { httpService } from '@/services'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'

new class extends UnitTestCase {
  protected test () {
    it('fetches the recently played songs', async () => {
      const songs = factory<Song[]>('song', 3)
      const getMock = this.mock(httpService, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await recentlyPlayedStore.fetch()

      expect(getMock).toHaveBeenCalledWith('recently-played')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(recentlyPlayedStore.state.songs).toEqual(songs)
    })

    it('fetches when attempting to add a new song and the state is empty', async () => {
      recentlyPlayedStore.state.songs = []
      const songs = factory<Song[]>('song', 3)
      const fetchMock = this.mock(recentlyPlayedStore, 'fetch').mockResolvedValue(songs)

      await recentlyPlayedStore.add(factory<Song>('song'))

      expect(fetchMock).toHaveBeenCalled()
    })

    it('adds a song to the state', async () => {
      const newSong = factory<Song>('song')
      const songs = factory<Song[]>('song', 10)
      const exceptSongs = songs.slice(0, 7)

      // We don't want to keep the reference to the original songs
      recentlyPlayedStore.state.songs = JSON.parse(JSON.stringify(songs))
      recentlyPlayedStore.excerptState.songs = JSON.parse(JSON.stringify(exceptSongs))

      await recentlyPlayedStore.add(newSong)

      expect(recentlyPlayedStore.state.songs).toEqual([newSong, ...songs])
      expect(recentlyPlayedStore.excerptState.songs).toEqual([newSong, ...songs.slice(0, 6)])
    })

    it('deduplicates when adding a song to the state', async () => {
      const songs = factory<Song[]>('song', 10)
      const newSong = songs[1]
      const exceptSongs = songs.slice(0, 7)

      // We don't want to keep the reference to the original songs
      recentlyPlayedStore.state.songs = JSON.parse(JSON.stringify(songs))
      recentlyPlayedStore.excerptState.songs = JSON.parse(JSON.stringify(exceptSongs))

      await recentlyPlayedStore.add(newSong)

      expect(recentlyPlayedStore.state.songs).toEqual([newSong, songs[0], ...songs.slice(2)])
      expect(recentlyPlayedStore.excerptState.songs).toEqual([newSong, songs[0], ...songs.slice(2, 7)])
    })
  }
}
