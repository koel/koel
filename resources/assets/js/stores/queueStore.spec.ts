import { reactive } from 'vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from 'factoria'
import { http } from '@/services'
import { queueStore, songStore } from '.'

let songs

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
      queueStore.queue(song)

      expect(queueStore.all).toHaveLength(4)
      expect(queueStore.last).toEqual(song)
    })

    it('queues to top', () => {
      const song = factory<Song>('song')
      queueStore.queueToTop(song)

      expect(queueStore.all).toHaveLength(4)
      expect(queueStore.first).toEqual(song)
    })

    it('replaces the whole queue', () => {
      const newSongs = factory<Song>('song', 2)
      queueStore.replaceQueueWith(newSongs)

      expect(queueStore.all).toEqual(newSongs)
    })

    it('removes a song from queue', () => {
      queueStore.unqueue(songs[1])

      expect(queueStore.all).toEqual([songs[0], songs[2]])
    })

    it('removes multiple songs from queue', () => {
      queueStore.unqueue([songs[1], songs[0]])

      expect(queueStore.all).toEqual([songs[2]])
    })

    it('clears the queue', () => {
      queueStore.clear()
      expect(queueStore.state.songs).toHaveLength(0)
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

      await queueStore.fetchRandom(3)

      expect(getMock).toHaveBeenCalledWith('queue/fetch?order=rand&limit=3')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(queueStore.all).toEqual(songs)
    })

    it('fetches random songs to queue with a custom order', async () => {
      const songs = factory<Song>('song', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(songs)
      const syncMock = this.mock(songStore, 'syncWithVault', songs)

      await queueStore.fetchInOrder('title', 'desc', 3)

      expect(getMock).toHaveBeenCalledWith('queue/fetch?order=desc&sort=title&limit=3')
      expect(syncMock).toHaveBeenCalledWith(songs)
      expect(queueStore.all).toEqual(songs)
    })
  }
}
