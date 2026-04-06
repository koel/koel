import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import type { Events } from '@/config/events'
import { eventBus } from '@/utils/eventBus'
import Component from './HomeScreen.vue'

describe('homeScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    h.visit('/home')
    h.render(Component)
  }

  it('renders an empty state if no songs found', async () => {
    commonStore.state.song_length = 0
    h.mock(overviewStore, 'fetch')

    h.render(Component)

    screen.getByTestId('screen-empty-state')
  })

  it('renders overview components if applicable', async () => {
    commonStore.state.song_length = 100
    const fetchOverviewMock = h.mock(overviewStore, 'fetch')

    await renderComponent()

    expect(fetchOverviewMock).toHaveBeenCalled()

    ;[
      'most-played-songs',
      'recently-played-songs',
      'recently-added-albums',
      'recently-added-songs',
      'most-played-artists',
      'most-played-albums',
    ].forEach(id => screen.getByTestId(id))

    expect(screen.queryByTestId('screen-empty-state')).toBeNull()
  })

  it('transitions from empty state to content after first upload', async () => {
    commonStore.state.song_length = 0
    const fetchOverviewMock = h.mock(overviewStore, 'fetch')

    h.render(Component)

    // Verify empty state is shown
    screen.getByTestId('screen-empty-state')
    expect(screen.queryByTestId('recently-added-albums')).toBeNull()

    // Simulate what uploadService.handleUploadResult does
    commonStore.state.song_length += 1
    eventBus.emit('SONG_UPLOADED', h.factory('song'))

    await h.tick(2)

    // Empty state should be gone, sections should render
    expect(screen.queryByTestId('screen-empty-state')).toBeNull()
    screen.getByTestId('recently-added-albums')
    expect(fetchOverviewMock).toHaveBeenCalled()
  })

  it.each<[keyof Events]>([['SONGS_UPDATED'], ['SONGS_DELETED'], ['SONG_UPLOADED']])(
    'refreshes the overviews on %s event',
    async eventName => {
      // eslint-disable-line no-unexpected-multiline
      const fetchOverviewMock = h.mock(overviewStore, 'fetch')
      await renderComponent()

      eventBus.emit(eventName)

      expect(fetchOverviewMock).toHaveBeenCalled()
    },
  )
})
