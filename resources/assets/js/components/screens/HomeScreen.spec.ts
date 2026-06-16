import { fireEvent, screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import type { Events } from '@/config/events'
import { eventBus } from '@/utils/eventBus'
import Component from './HomeScreen.vue'

const openModalSpy = vi.fn()
vi.mock('@/composables/useModal', () => ({
  useModal: () => ({ openModal: openModalSpy, closeModal: vi.fn() }),
}))

const blockIdsInDom = (container: Element) =>
  Array.from(container.querySelectorAll<HTMLElement>('.home-sections > [data-testid]')).map(el => el.dataset.testid!)

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

  it.each<[keyof Events]>([['SONGS_UPDATED'], ['SONGS_DELETED'], ['SONG_UPLOADED']])(
    'refreshes the overviews on %s event',
    async eventName => {
      const fetchOverviewMock = h.mock(overviewStore, 'fetch')
      await renderComponent()

      eventBus.emit(eventName)

      expect(fetchOverviewMock).toHaveBeenCalled()
    },
  )

  it('renders the reorder trigger button when the library is not empty', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')

    h.render(Component)

    screen.getByTestId('reorder-home-blocks-btn')
  })

  it('hides the reorder trigger button on the empty state', () => {
    commonStore.state.song_length = 0
    h.mock(overviewStore, 'fetch')

    h.render(Component)

    expect(screen.queryByTestId('reorder-home-blocks-btn')).toBeNull()
  })

  it('opens the ReorderBlocksModal with the canonical block summaries when the trigger is clicked', async () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    openModalSpy.mockClear()

    h.render(Component)

    await fireEvent.click(screen.getByTestId('reorder-home-blocks-btn'))

    expect(openModalSpy).toHaveBeenCalledOnce()
    const [, props] = openModalSpy.mock.calls[0] as [unknown, { blocks: { id: string; label: string }[] }]
    expect(props.blocks).toEqual(
      expect.arrayContaining([
        { id: 'recently-played-songs', label: 'Recently Played' },
        { id: 'random-artists', label: 'Random Artists' },
      ]),
    )
  })

  it('honors preferenceStore.home_blocks_order when rendering blocks', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    preferenceStore.temporary.home_blocks_order = ['random-songs', 'recently-added-albums']

    const { container } = h.render(Component)
    const ids = blockIdsInDom(container)

    expect(ids[0]).toBe('random-songs')
    expect(ids[1]).toBe('recently-added-albums')
    expect(ids).toContain('most-played-songs')
  })
})
