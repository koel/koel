import UnitTestCase from '@/__tests__/UnitTestCase'
import data from '@/__tests__/blobs/data'
import { queueStore } from '@/stores/queueStore'
import { expect, it } from 'vitest'

const ARTIST_ID = 5
let songs
let songToQueue: Song

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      songs = data.songs.filter(song => song.artist_id === ARTIST_ID)
      queueStore.state.songs = songs
      queueStore.state.current = songs[1]

      songToQueue = data.songs[0]
    })
  }

  protected test () {
    it('returns all queued songs', () => expect(queueStore.all).toEqual(songs))
    it('returns the first queued song', () => expect(queueStore.first.title).toBe('No bravery'))
    it('returns the last queued song', () => expect(queueStore.last.title).toBe('Tears and rain'))

    it('appends a song to end of the queue', () => {
      queueStore.queue(songToQueue)
      expect(queueStore.last.title).toBe('I Swear')
    })

    it('prepends a song to top of the queue', () => {
      queueStore.queueToTop(songToQueue)
      expect(queueStore.first.title).toBe('I Swear')
    })

    it('replaces the whole queue', () => {
      queueStore.replaceQueueWith(songToQueue)
      expect(queueStore.all).toHaveLength(1)
      expect(queueStore.first.title).toBe('I Swear')
    })

    it('removes a song from queue', () => {
      queueStore.unqueue(queueStore.state.songs[0])
      expect(queueStore.first.title).toBe('So long, Jimmy')
    })

    it('removes multiple songs from queue', () => {
      queueStore.unqueue([queueStore.state.songs[0], queueStore.state.songs[1]])
      expect(queueStore.first.title).toBe('Wisemen')
    })

    it('removes all songs from queue', () => {
      queueStore.clear()
      expect(queueStore.state.songs).toHaveLength(0)
    })

    it('returns the current song', () => expect(queueStore.current?.title).toBe('So long, Jimmy'))

    it('sets the current song', () => {
      expect(queueStore.current!.title).toBe('No bravery')
    })

    it('gets the next song in queue', () => expect(queueStore.next?.title).toBe('Wisemen'))

    it('returns undefined as next song if at end of queue', () => {
      expect(queueStore.next).toBeUndefined()
    })

    it('gets the previous song in queue', () => expect(queueStore.previous?.title).toBe('No bravery'))

    it('returns undefined as previous song if at beginning of queue', () => {
      expect(queueStore.previous).toBeUndefined()
    })
  }
}
