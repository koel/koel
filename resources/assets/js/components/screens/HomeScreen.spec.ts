import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { overviewStore } from '@/stores/overviewStore'
import { preferenceStore } from '@/stores/preferenceStore'
import type { Events } from '@/config/events'
import { eventBus } from '@/utils/eventBus'
import Component from './HomeScreen.vue'

const blockIdsInDom = (container: Element) =>
  Array.from(container.querySelectorAll<HTMLElement>('.reorderable-list-item__inner > [data-testid]')).map(
    el => el.dataset.testid!,
  )

const dispatch = (target: EventTarget, type: string, init: Record<string, unknown> = {}) => {
  const event = new Event(type, { bubbles: true, cancelable: true })
  for (const [key, value] of Object.entries(init)) {
    Object.defineProperty(event, key, { value, configurable: true })
  }
  target.dispatchEvent(event)
  return event
}

const stubRect = (el: HTMLElement, top: number, height = 100) => {
  el.getBoundingClientRect = () =>
    ({
      top,
      bottom: top + height,
      height,
      left: 0,
      right: 500,
      width: 500,
      x: 0,
      y: top,
      toJSON: () => ({}),
    }) as DOMRect
}

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

  it('honors the saved home_blocks_order, appending unknown blocks last', () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    preferenceStore.temporary.home_blocks_order = ['random-songs', 'recently-added-albums']

    const { container } = h.render(Component)

    const ids = blockIdsInDom(container)
    expect(ids[0]).toBe('random-songs')
    expect(ids[1]).toBe('recently-added-albums')
    expect(ids).toContain('most-played-songs')
  })

  it('persists a new order to preferences when a drop is committed', async () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    preferenceStore.temporary.home_blocks_order = []

    const { container } = h.render(Component)
    const wrappers = Array.from(container.querySelectorAll<HTMLElement>('.reorderable-list-item'))
    const sourceWrapper = wrappers[0]
    const targetWrapper = wrappers[2]
    const sourceHeading = sourceWrapper.querySelector<HTMLHeadingElement>('h3')!

    stubRect(sourceWrapper, 0)
    stubRect(targetWrapper, 200)

    dispatch(sourceHeading, 'dragstart')
    await nextTick()
    dispatch(targetWrapper, 'dragover', { clientY: 260 })
    dispatch(targetWrapper, 'drop')
    await nextTick()

    // wrappers[2] is the ReorderableListItem for similar-songs (its inner
    // component is hidden by a v-if in test conditions, but the wrapper
    // and its id survive). The source must end up after that target.
    const saved = preferenceStore.home_blocks_order
    const sourceIdx = saved.indexOf('recently-played-songs')
    const targetIdx = saved.indexOf('similar-songs')
    expect(sourceIdx).toBeGreaterThan(targetIdx)
  })

  it('does not write a preference when the drag is released without a drop', async () => {
    commonStore.state.song_length = 100
    h.mock(overviewStore, 'fetch')
    preferenceStore.temporary.home_blocks_order = []

    const { container } = h.render(Component)
    const wrappers = Array.from(container.querySelectorAll<HTMLElement>('.reorderable-list-item'))
    const sourceWrapper = wrappers[0]
    const targetWrapper = wrappers[2]
    const sourceHeading = sourceWrapper.querySelector<HTMLHeadingElement>('h3')!

    stubRect(sourceWrapper, 0)
    stubRect(targetWrapper, 200)

    dispatch(sourceHeading, 'dragstart')
    await nextTick()
    dispatch(targetWrapper, 'dragover', { clientY: 260 })
    await nextTick()
    dispatch(document, 'dragend')
    await nextTick()

    expect(preferenceStore.home_blocks_order).toEqual([])
  })
})
