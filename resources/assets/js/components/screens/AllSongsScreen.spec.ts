import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, nextTick } from 'vue'
import { SelectedPlayablesKey } from '@/config/symbols'
import { requireInjection } from '@/utils/helpers'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
import { eventBus } from '@/utils/eventBus'
import Component from './AllSongsScreen.vue'

describe('allSongsScreen.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.song_count = 420
      commonStore.state.song_length = 123_456
      playableStore.state.playables = h.factory('song').make(20)
      h.actingAsUser()
    },
  })

  const renderComponent = async () => {
    const fetchMock = h.mock(playableStore, 'paginateSongs').mockResolvedValue('next-cursor-token')

    h.router.$currentRoute.value = {
      screen: 'Songs',
      path: '/songs',
    }

    const rendered = h.render(Component, {
      global: {
        stubs: {
          SongList: h.stub('song-list'),
        },
      },
    })

    await waitFor(() =>
      expect(fetchMock).toHaveBeenCalledWith({
        sort: 'title',
        order: 'asc',
        cursor: '',
      }),
    )

    return [rendered, fetchMock] as const
  }

  it('shuffles', async () => {
    h.createAudioPlayer()

    const queueMock = h.mock(queueStore, 'fetchRandom')
    const playMock = h.mock(playbackService, 'playFirstInQueue')
    const goMock = h.mock(Router, 'go')
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Shuffle All' }))

    await waitFor(() => {
      expect(queueMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalled()
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('keeps a new sort made while a page is loading', async () => {
    const staleSongs = h.factory('song').make(5)
    const sortedSongs = h.factory('song').make(5)
    let resolveStalePage: (cursor: string) => void = () => {}

    const paginateMock = h
      .mock(playableStore, 'paginateSongs')
      .mockImplementationOnce(
        () =>
          new Promise<string>(resolve => {
            resolveStalePage = cursor => {
              playableStore.state.playables.push(...staleSongs)
              resolve(cursor)
            }
          }),
      )
      .mockImplementationOnce(async () => {
        playableStore.state.playables.push(...sortedSongs)
        return null
      })

    h.router.$currentRoute.value = {
      screen: 'Songs',
      path: '/songs',
    }

    h.render(Component, {
      global: {
        stubs: {
          SongList: defineComponent({
            emits: ['sort'],
            template: `<button data-testid="song-list" @click="$emit('sort', 'artist_name', 'desc')" />`,
          }),
        },
      },
    })

    await h.user.click(screen.getByTestId('song-list'))
    resolveStalePage('stale-cursor')

    await waitFor(() => {
      expect(paginateMock).toHaveBeenCalledTimes(2)
      expect(paginateMock).toHaveBeenLastCalledWith({ sort: 'artist_name', order: 'desc', cursor: '' })
      expect(playableStore.state.playables).toEqual(sortedSongs)
    })
  })

  it('refetches from the start when refreshed', async () => {
    const [, fetchMock] = await renderComponent()
    fetchMock.mockClear()

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith({ sort: 'title', order: 'asc', cursor: '' }))
  })

  it('clears the selection when refreshed', async () => {
    h.mock(playableStore, 'paginateSongs').mockResolvedValue(null)

    h.router.$currentRoute.value = {
      screen: 'Songs',
      path: '/songs',
    }

    h.render(Component, {
      global: {
        stubs: {
          SongList: defineComponent({
            setup: () => {
              const [, setSelectedPlayables] = requireInjection<[unknown, Closure]>(SelectedPlayablesKey)
              return { select: () => setSelectedPlayables(playableStore.state.playables.slice(0, 2)) }
            },
            template: `<button data-testid="song-list" @click="select" />`,
          }),
        },
      },
    })

    await h.user.click(screen.getByTestId('song-list'))
    expect(screen.queryByTestId('btn-shuffle-selected')).not.toBeNull()

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    await waitFor(() => expect(screen.queryByTestId('btn-shuffle-selected')).toBeNull())
  })

  it('refetches from the start when revisited after an upload', async () => {
    const [, fetchMock] = await renderComponent()
    fetchMock.mockClear()

    eventBus.emit('SONG_UPLOADED', h.factory('song').make())
    eventBus.emit('SONG_UPLOADED', h.factory('song').make())

    expect(fetchMock).not.toHaveBeenCalled()

    h.visit('/home')
    await nextTick()
    h.visit('/songs')

    await waitFor(() => expect(fetchMock).toHaveBeenCalledOnce())
    expect(fetchMock).toHaveBeenCalledWith({ sort: 'title', order: 'asc', cursor: '' })
  })

  it('offers a reload on the empty state when the library has songs', async () => {
    playableStore.state.playables = []
    const [, fetchMock] = await renderComponent()
    fetchMock.mockClear()

    await h.user.click(screen.getByRole('button', { name: 'Reload' }))

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith({ sort: 'title', order: 'asc', cursor: '' }))
  })

  it('does not offer a reload on the empty state when the library is empty', async () => {
    commonStore.state.song_count = 0
    playableStore.state.playables = []
    await renderComponent()

    expect(screen.queryByRole('button', { name: 'Reload' })).toBeNull()
  })
})
