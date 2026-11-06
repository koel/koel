import { defineComponent, nextTick } from 'vue'
import { fireEvent, screen } from '@testing-library/vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { Filter } from '@/config/hooks'
import { addFilter, type HookHandle, removeFilter } from '@/hooks'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import type { Events } from '@/config/events'
import { eventBus } from '@/utils/eventBus'
import Component, { type HomeBlock } from './HomeScreen.vue'

const openModalSpy = vi.fn()
vi.mock('@/composables/useModal', () => ({
  useModal: () => ({ openModal: openModalSpy, closeModal: vi.fn() }),
}))

const blockIdsInDom = (container: Element) =>
  Array.from(container.querySelectorAll<HTMLElement>('.home-sections > [data-testid]')).map(el => el.dataset.testid!)

const builtInBlockIds = [
  'recently-played-songs',
  'recently-added-albums',
  'similar-songs',
  'most-played-albums',
  'most-played-songs',
  'most-played-artists',
  'recently-added-songs',
  'recently-added-artists',
  'least-played-songs',
  'random-songs',
  'random-albums',
  'random-artists',
]

const CustomBlock = defineComponent({ template: '<section />' })

describe('homeScreen.vue', () => {
  const h = createHarness()

  let handle: HookHandle | null = null

  afterEach(() => {
    if (handle) {
      removeFilter(handle)
      handle = null
    }
  })

  const addCustomBlockAfterRecentlyPlayed = () => {
    handle = addFilter<HomeBlock[]>(Filter.HOME_BLOCKS, blocks => [
      blocks[0],
      { id: 'custom-block', label: 'Custom', component: CustomBlock },
      ...blocks.slice(1),
    ])
  }

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

  it.each<[keyof Events]>([['SONGS_UPDATED'], ['SONGS_DELETED'], ['ALBUM_MERGED'], ['ARTIST_MERGED']])(
    'refreshes the overviews on %s event',
    async eventName => {
      const fetchOverviewMock = h.mock(overviewStore, 'fetch')
      await renderComponent()

      eventBus.emit(eventName)

      expect(fetchOverviewMock).toHaveBeenCalled()
    },
  )

  it('refreshes the overviews once when shown again after songs are uploaded', async () => {
    const fetchOverviewMock = h.mock(overviewStore, 'fetch')
    await renderComponent()
    fetchOverviewMock.mockClear()

    eventBus.emit('SONG_UPLOADED', h.factory('song').make())
    eventBus.emit('SONG_UPLOADED', h.factory('song').make())
    eventBus.emit('SONG_UPLOADED', h.factory('song').make())

    expect(fetchOverviewMock).not.toHaveBeenCalled()

    h.visit('/songs')
    await nextTick()
    h.visit('/home')
    await nextTick()

    expect(fetchOverviewMock).toHaveBeenCalledOnce()
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
    const savedOrder = [...builtInBlockIds].reverse()
    preferenceStore.temporary.home_blocks_order = savedOrder

    const { container } = h.render(Component)

    expect(blockIdsInDom(container)).toEqual(savedOrder)
  })

  it('renders an added block missing from the saved order right after its predecessor', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    addCustomBlockAfterRecentlyPlayed()
    preferenceStore.temporary.home_blocks_order = [
      ...builtInBlockIds.slice(1, 4),
      'recently-played-songs',
      ...builtInBlockIds.slice(4),
    ]

    const { container } = h.render(Component)

    expect(blockIdsInDom(container)).toEqual([
      ...builtInBlockIds.slice(1, 4),
      'recently-played-songs',
      'custom-block',
      ...builtInBlockIds.slice(4),
    ])
  })

  it('renders an added block at its saved position', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    addCustomBlockAfterRecentlyPlayed()
    preferenceStore.temporary.home_blocks_order = [
      ...builtInBlockIds.slice(0, 5),
      'custom-block',
      ...builtInBlockIds.slice(5),
    ]

    const { container } = h.render(Component)

    expect(blockIdsInDom(container)).toEqual([
      ...builtInBlockIds.slice(0, 5),
      'custom-block',
      ...builtInBlockIds.slice(5),
    ])
  })

  it('ignores saved block ids without a matching block', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    preferenceStore.temporary.home_blocks_order = ['removed-block', ...builtInBlockIds]

    const { container } = h.render(Component)

    expect(blockIdsInDom(container)).toEqual(builtInBlockIds)
  })

  it('renders an added block without a predecessor first', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    handle = addFilter<HomeBlock[]>(Filter.HOME_BLOCKS, blocks => [
      { id: 'custom-block', label: 'Custom', component: CustomBlock },
      ...blocks,
    ])
    preferenceStore.temporary.home_blocks_order = [...builtInBlockIds].reverse()

    const { container } = h.render(Component)

    expect(blockIdsInDom(container)).toEqual(['custom-block', ...[...builtInBlockIds].reverse()])
  })
})
