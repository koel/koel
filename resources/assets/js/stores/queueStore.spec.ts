import { reactive } from 'vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from 'factoria'
import { http } from '@/services'
import { queueStore, songStore } from '.'

let songs: Song[]

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      songs = factory<Song>('song', 3)
      queueStore.state.songs = reactive(songs)
    })
  }

  protected test () {
    it('returns all queued songs', () => expect(queueStore.all).toEqual(songs))

    it('returns the first queued song', () => expect(queueStore.first).toEqual(songs[0]))

    it('returns the last queued song', () => expect(queueStore.last).toEqual(songs[2]))

    it('queues to bottom', () => {
      const song = factory<Song>('song')
      const putMock = this.mock(http, 'put')
      queueStore.queue(song)

      expect(queueStore.all).toHaveLength(4)
      expect(queueStore.last).toEqual(song)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
    })

    it('queues to top', () => {
      const song = factory<Song>('song')
      const putMock = this.mock(http, 'put')
      queueStore.queueToTop(song)

      expect(queueStore.all).toHaveLength(4)
      expect(queueStore.first).toEqual(song)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
    })

    it('replaces the whole queue', () => {
      const newSongs = factory<Song>('song', 2)
      const putMock = this.mock(http, 'put')
      queueStore.replaceQueueWith(newSongs)

      expect(queueStore.all).toEqual(newSongs)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: newSongs.map(song => song.id) })
    })

    it('removes a song from queue', () => {
      const putMock = this.mock(http, 'put')
      queueStore.unqueue(songs[1])

      expect(queueStore.all).toEqual([songs[0], songs[2]])
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
    })

    it('removes multiple songs from queue', () => {
      const putMock = this.mock(http, 'put')
      queueStore.unqueue([songs[1], songs[0]])

      expect(queueStore.all).toEqual([songs[2]])
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
    })

    it('clears the queue', () => {
      const putMock = this.mock(http, 'put')
      queueStore.clear()

      expect(queueStore.state.songs).toHaveLength(0)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: [] })
    })

    it.each<[PlaybackState]>([['Playing'], ['Paused']])('identifies the current song by %s state', state => {
      queueStore.state.songs[1].playback_state = state
      expect(queueStore.current).toEqual(queueStore.state.songs[1])
    })

    it('gets the next song in queue', () => {
      queueStore.state.songs[1].playback_state = 'Playing'
      expect(queueStore.next).toEqual(queueStore.state.songs[2])
    })

    it('returns undefined as next song if at end of queue', () => {
      queueStore.state.songs[2].playback_state = 'Playing'
      expect(queueStore.next).toBeUndefined()
    })

    it('gets the previous song in queue', () => {
      queueStore.state.songs[1].playback_state = 'Playing'
      expect(queueStore.previous).toEqual(queueStore.state.songs[0])
    })

    it('returns undefined as previous song if at beginning of queue', () => {
      queueStore.state.songs[0].playback_state = 'Playing'
      expect(queueStore.previous).toBeUndefined()
    })

    it('fetches random songs to queue', async () => {
      const songs = factory<Song>('song', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)
      const putMock = this.mock(http, 'put')

      await queueStore.fetchRandom(3)

      expect(getMock).toHaveBeenCalledWith('queue/fetch?order=rand&limit=3')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(queueStore.all).toEqual(songs)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: songs.map(song => song.id) })
    })

    it('fetches random songs to queue with a custom order', async () => {
      const songs = factory<Song>('song', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)
      const putMock = this.mock(http, 'put')

      await queueStore.fetchInOrder('title', 'desc', 3)

      expect(getMock).toHaveBeenCalledWith('queue/fetch?order=desc&sort=title&limit=3')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(queueStore.all).toEqual(songs)
      expect(putMock).toHaveBeenCalledWith('queue/state', { songs: songs.map(song => song.id) })
    })
  }
}
