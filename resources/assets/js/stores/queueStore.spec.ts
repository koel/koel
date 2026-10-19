import { reactive } from 'vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { queueStore } from '@/stores/queueStore'

describe('queueStore', () => {
  let songs: Song[] = []

  const h = createHarness({
    beforeEach: () => {
      playableStore.vault.clear()
      songs = playableStore.syncWithVault(h.factory('song').make(3)) as Song[]
      queueStore.state.playables = reactive(songs)
      vi.useFakeTimers()
    },
  })

  afterEach(() => {
    vi.runOnlyPendingTimers()
    vi.useRealTimers()
    vi.restoreAllMocks()
  })

  it('returns the first queued song', () => expect(queueStore.first).toEqual(songs[0]))

  it('returns the last queued song', () => expect(queueStore.last).toEqual(songs[2]))

  it('queues to bottom', async () => {
    const song = h.factory('song').make()
    const putMock = h.mock(http, 'put')
    queueStore.queue(song)

    expect(queueStore.all).toHaveLength(4)
    expect(queueStore.last).toEqual(song)
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
  })

  it('keeps the playing song current when it is queued again', () => {
    songs[1].playback_state = 'Playing'

    queueStore.queue([songs[1], h.factory('song').make()])

    expect(queueStore.current?.id).toBe(songs[1].id)
  })

  it('queues after the playing song even when the playing song is among the queued ones', () => {
    songs[0].playback_state = 'Playing'
    const newSong = h.factory('song').make()

    queueStore.queueAfterCurrent([songs[0], newSong])

    expect(queueStore.current?.id).toBe(songs[0].id)
    expect(queueStore.all.map(({ id }) => id)).toEqual([songs[0].id, newSong.id, songs[1].id, songs[2].id])
  })

  it('queues to top', () => {
    const song = h.factory('song').make()
    const putMock = h.mock(http, 'put')
    queueStore.queueToTop(song)

    expect(queueStore.all).toHaveLength(4)
    expect(queueStore.first).toEqual(song)
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
  })

  it('replaces the whole queue', () => {
    const newSongs = h.factory('song').make(2)
    const putMock = h.mock(http, 'put')
    queueStore.replaceQueueWith(newSongs)

    expect(queueStore.all).toEqual(newSongs)
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: newSongs.map(song => song.id) })
  })

  it('removes a song from queue', () => {
    const putMock = h.mock(http, 'put')
    queueStore.unqueue(songs[1])

    expect(queueStore.all).toEqual([songs[0], songs[2]])
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
  })

  it('removes multiple songs from queue', () => {
    const putMock = h.mock(http, 'put')
    queueStore.unqueue([songs[1], songs[0]])

    expect(queueStore.all).toEqual([songs[2]])
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
  })

  it('clears the queue', () => {
    const putMock = h.mock(http, 'put')
    queueStore.clear()

    expect(queueStore.state.playables).toHaveLength(0)
    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: [] })
  })

  it('saves the queue once when queueing to bottom', () => {
    const saveStateMock = h.mock(queueStore, 'saveState')

    queueStore.queue(h.factory('song').make())

    expect(saveStateMock).toHaveBeenCalledOnce()
  })

  it('saves the queue once when queueing after the current song', () => {
    songs[0].playback_state = 'Playing'
    const saveStateMock = h.mock(queueStore, 'saveState')

    queueStore.queueAfterCurrent(h.factory('song').make())

    expect(saveStateMock).toHaveBeenCalledOnce()
  })

  it('saves rapid queue changes in one request with the final state', () => {
    const putMock = h.mock(http, 'put')

    queueStore.queue(h.factory('song').make())
    queueStore.unqueue(songs[0])
    queueStore.queueToTop(h.factory('song').make())

    expect(putMock).not.toHaveBeenCalled()

    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledOnce()
    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: queueStore.all.map(song => song.id) })
  })

  it('saves a pending queue change right away when the page gets hidden', () => {
    const putMock = h.mock(http, 'put')
    vi.spyOn(document, 'visibilityState', 'get').mockReturnValue('hidden')

    queueStore.unqueue(songs[0])
    document.dispatchEvent(new Event('visibilitychange'))

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: [songs[1].id, songs[2].id] })
  })

  it.each<[PlaybackState]>([['Playing'], ['Paused']])('identifies the current song by %s state', state => {
    queueStore.state.playables[1].playback_state = state
    expect(queueStore.current).toEqual(queueStore.state.playables[1])
  })

  it('keeps the current song after it is replaced out of the queue', () => {
    h.mock(http, 'put')
    songs[1].playback_state = 'Playing'
    queueStore.replaceQueueWith(h.factory('song').make(2))

    expect(queueStore.current).toBe(songs[1])

    songs[1].playback_state = 'Stopped'

    expect(queueStore.current).toBeUndefined()
  })

  it('gets the next song in queue', () => {
    queueStore.state.playables[1].playback_state = 'Playing'
    expect(queueStore.next).toEqual(queueStore.state.playables[2])
  })

  it('returns undefined as next song if at end of queue', () => {
    queueStore.state.playables[2].playback_state = 'Playing'
    expect(queueStore.next).toBeUndefined()
  })

  it('gets the previous song in queue', () => {
    queueStore.state.playables[1].playback_state = 'Playing'
    expect(queueStore.previous).toEqual(queueStore.state.playables[0])
  })

  it('returns undefined as previous song if at beginning of queue', () => {
    queueStore.state.playables[0].playback_state = 'Playing'
    expect(queueStore.previous).toBeUndefined()
  })

  it('fetches random songs to queue', async () => {
    const songs = h.factory('song').make(3)
    const getMock = h.mock(http, 'get').mockResolvedValue(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)
    const putMock = h.mock(http, 'put')

    await queueStore.fetchRandom(3)

    expect(getMock).toHaveBeenCalledWith('queue/fetch?order=rand&limit=3')
    expect(syncMock).toHaveBeenCalledWith(songs)
    expect(queueStore.all).toEqual(songs)

    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: songs.map(song => song.id) })
  })

  it('fetches random songs to queue with a custom order', async () => {
    const songs = h.factory('song').make(3)
    const getMock = h.mock(http, 'get').mockResolvedValue(songs)
    const syncMock = h.mock(playableStore, 'syncWithVault', songs)
    const putMock = h.mock(http, 'put')

    await queueStore.fetchInOrder('title', 'desc', 3)

    expect(getMock).toHaveBeenCalledWith('queue/fetch?order=desc&sort=title&limit=3')
    expect(syncMock).toHaveBeenCalledWith(songs)
    expect(queueStore.all).toEqual(songs)

    vi.runAllTimers()

    expect(putMock).toHaveBeenCalledWith('queue/state', { songs: songs.map(song => song.id) })
  })

  it('queues the shared copies of fetched songs that are already loaded', async () => {
    h.mock(http, 'get').mockResolvedValue([{ ...songs[1] }])
    h.mock(http, 'put')

    await queueStore.fetchRandom(1)

    expect(queueStore.all[0]).toBe(songs[1])
  })
})
