import Component from '@/components/song/SongCard.vue'
import factory from '@/__tests__/factory'
import { queueStore } from '@/stores'
import { playback } from '@/services'
import { mock } from '@/__tests__/__helpers__'
import { Wrapper, shallow } from '@/__tests__/adapter'
import FunctionPropertyNames = jest.FunctionPropertyNames

describe('components/song/SongCard', () => {
  let propsData, song: Song, wrapper: Wrapper

  beforeEach(() => {
    song = factory<Song>('song', {
      artist: factory<Artist>('artist', {
        id: 42,
        name: 'Foo Fighter'
      }),
      playCount: 10,
      playbackState: 'Stopped',
      title: 'Foo bar'
    })

    propsData = {
      song,
      topPlayCount: 42
    }

    wrapper = shallow(Component, { propsData })
  })

  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it.each([[true, false], [false, true]])('queuing and playing behavior', (shouldQueue, queued) => {
    const containsStub = mock(queueStore, 'contains', queued)
    const queueStub = mock(queueStore, 'queueAfterCurrent')
    const playStub = mock(playback, 'play')
    wrapper.dblclick('[data-test=song-card]')
    expect(containsStub).toHaveBeenCalledWith(song)
    if (queued) {
      expect(queueStub).not.toHaveBeenCalled()
    } else {
      expect(queueStub).toHaveBeenCalledWith(song)
    }
    expect(playStub).toHaveBeenCalledWith(song)
  })

  it.each<[PlaybackState, FunctionPropertyNames<typeof playback>]>([
    ['Stopped', 'play'],
    ['Playing', 'pause'],
    ['Paused', 'resume']
  ])('if state is currently "%s", %s', (state, action) => {
    const m = mock(playback, action)
    song.playbackState = state
    wrapper.click('.cover .control')
    expect(m).toHaveBeenCalled()
  })
})
