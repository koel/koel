// @ts-nocheck
import { queueStore } from '@/stores'
import data from '@/__tests__/blobs/data'

describe('stores/queue', () => {
  const JAMES_BLUNT_ARTIST_ID = 5
  let songs

  beforeEach(() => {
    songs = data.songs.filter(song => song.artist_id === JAMES_BLUNT_ARTIST_ID)
    queueStore.state.songs = songs
    queueStore.state.current = songs[1]
  })

  it('returns all queued songs', () => {
    expect(queueStore.all).toBe(songs)
  })

  it('returns the first queued song', () => {
    expect(queueStore.first.title).toBe('No bravery')
  })

  it('returns the last queued song', () => {
    expect(queueStore.last.title).toBe('Tears and rain')
  })

  describe('queues and unqueues', () => {
    let thatSongByAll4One

    beforeEach(() => {
      queueStore.state.songs = songs
      thatSongByAll4One = data.songs[0]
    })

    it('appends a song to end of the queue', () => {
      queueStore.queue(thatSongByAll4One)
      expect(queueStore.last.title).toBe('I Swear')
    })

    it('prepends a song to top of the queue', () => {
      queueStore.queueToTop(thatSongByAll4One)
      expect(queueStore.first.title).toBe('I Swear')
    })

    it('replaces the whole queue', () => {
      queueStore.replaceQueueWith(data.songs[0])
      expect(queueStore.all).toHaveLength(1)
      expect(queueStore.first.title).toBe('I Swear')
    })

    it('removes a song from queue', () => {
      queueStore.unqueue(queueStore.state.songs[0])
      expect(queueStore.first.title).toBe('So long, Jimmy') // Oh the irony.
    })

    it('removes mutiple songs from queue', () => {
      queueStore.unqueue([queueStore.state.songs[0], queueStore.state.songs[1]])
      expect(queueStore.first.title).toBe('Wisemen')
    })

    it('removes all songs from queue', () => {
      queueStore.clear()
      expect(queueStore.state.songs).toHaveLength(0)
    })
  })

  it('returns the current song', () => {
    expect(queueStore.current.title).toBe('So long, Jimmy')
  })

  it('sets the current song', () => {
    queueStore.current = queueStore.state.songs[0]
    expect(queueStore.current.title).toBe('No bravery')
  })

  it('gets the next song in queue', () => {
    expect(queueStore.next.title).toBe('Wisemen')
  })

  it('returns undefined as next song if at end of queue', () => {
    queueStore.current = queueStore.state.songs[queueStore.state.songs.length - 1]
    expect(queueStore.next).toBeUndefined()
  })

  it('gets the previous song in queue', () => {
    expect(queueStore.previous.title).toBe('No bravery')
  })

  it('returns undefined as previous song if at beginning of queue', () => {
    queueStore.current = queueStore.state.songs[0]
    expect(queueStore.previous).toBeUndefined()
  })
})
