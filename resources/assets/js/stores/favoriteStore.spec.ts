import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { favoriteStore } from '.'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => (favoriteStore.state.playables = []))
  }

  protected test () {
    it('toggles one song', async () => {
      const addMock = this.mock(favoriteStore, 'add')
      const removeMock = this.mock(favoriteStore, 'remove')
      const postMock = this.mock(http, 'post')
      const song = factory('song', { liked: false })

      await favoriteStore.toggleOne(song)

      expect(postMock).toHaveBeenNthCalledWith(1, `interaction/like`, { song: song.id })
      expect(addMock).toHaveBeenCalledWith(song)
      expect(song.liked).toBe(true)

      await favoriteStore.toggleOne(song)
      expect(postMock).toHaveBeenNthCalledWith(2, `interaction/like`, { song: song.id })
      expect(removeMock).toHaveBeenCalledWith(song)
      expect(song.liked).toBe(false)
    })

    it('adds songs', () => {
      const songs = factory('song', 3)
      favoriteStore.add(songs)
      expect(favoriteStore.state.playables).toEqual(songs)

      // doesn't duplicate songs
      favoriteStore.add(songs[0])
      expect(favoriteStore.state.playables).toEqual(songs)
    })

    it('removes songs', () => {
      const songs = factory('song', 3)
      favoriteStore.state.playables = songs
      favoriteStore.remove(songs)
      expect(favoriteStore.state.playables).toEqual([])
    })

    it('likes several songs', async () => {
      const songs = factory('song', 3)
      const addMock = this.mock(favoriteStore, 'add')
      const postMock = this.mock(http, 'post')

      await favoriteStore.like(songs)

      expect(postMock).toHaveBeenCalledWith(`interaction/batch/like`, { songs: songs.map(song => song.id) })
      expect(addMock).toHaveBeenCalledWith(songs)
    })

    it('unlikes several songs', async () => {
      const songs = factory('song', 3)
      const removeMock = this.mock(favoriteStore, 'remove')
      const postMock = this.mock(http, 'post')

      await favoriteStore.unlike(songs)

      expect(postMock).toHaveBeenCalledWith(`interaction/batch/unlike`, { songs: songs.map(song => song.id) })
      expect(removeMock).toHaveBeenCalledWith(songs)
    })

    it('fetches favorites', async () => {
      const songs = factory('song', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(songs)

      await favoriteStore.fetch()

      expect(getMock).toHaveBeenCalledWith('songs/favorite')
      expect(favoriteStore.state.playables).toEqual(songs)
    })
  }
}
