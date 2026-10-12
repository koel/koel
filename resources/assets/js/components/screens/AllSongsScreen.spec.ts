import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import { commonStore } from '@/stores/commonStore'
import { queueStore } from '@/stores/queueStore'
import { playableStore } from '@/stores/playableStore'
import { playbackService } from '@/services/QueuePlaybackService'
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
})
