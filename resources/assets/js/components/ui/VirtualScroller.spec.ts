import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './VirtualScroller.vue'

describe('virtualScroller.vue', () => {
  const h = createHarness()

  it('exposes scrollToIndex that scrolls to the correct position', () => {
    const items = Array.from({ length: 100 }, (_, i) => ({ id: i, name: `Item ${i}` }))
    const itemHeight = 64

    const { container } = h.render(Component, {
      props: { items, itemHeight },
      slots: {
        default: (props: { item: { name: string } }) => props.item.name,
      },
    })

    const scrollerEl = container.querySelector('.virtual-scroller') as HTMLElement
    const scrollToMock = vi.fn()
    scrollerEl.scrollTo = scrollToMock

    const instance = (scrollerEl as any)['__vueParentComponent']
    instance?.exposed?.scrollToIndex(50)

    expect(scrollToMock).toHaveBeenCalledWith(expect.objectContaining({ behavior: 'smooth' }))
  })

  it('scrolls to an index only when its item is out of view', () => {
    const items = Array.from({ length: 100 }, (_, i) => ({ id: i, name: `Item ${i}` }))

    const { container } = h.render(Component, {
      props: { items, itemHeight: 64 },
      slots: {
        default: (props: { item: { name: string } }) => props.item.name,
      },
    })

    const scrollerEl = container.querySelector<HTMLElement>('.virtual-scroller')!
    const scrollToMock = vi.fn()
    scrollerEl.scrollTo = scrollToMock
    Object.defineProperty(scrollerEl, 'clientHeight', { value: 640 })

    const instance = (scrollerEl as any)['__vueParentComponent']

    instance?.exposed?.scrollToIndexIfHidden(2)
    expect(scrollToMock).not.toHaveBeenCalled()

    instance?.exposed?.scrollToIndexIfHidden(50)
    expect(scrollToMock).toHaveBeenCalledOnce()
  })

  it('shows placeholders after the items while loading more', async () => {
    const { rerender } = h.render(Component, {
      props: { items: [{ id: 1 }, { id: 2 }], itemHeight: 64, loadingMore: true },
      slots: {
        default: '<div data-testid="row" />',
        placeholder: '<div data-testid="row-placeholder" />',
      },
    })

    await h.tick()

    expect(screen.getAllByTestId('row')).toHaveLength(2)
    expect(screen.getAllByTestId('row-placeholder')).toHaveLength(3)

    await rerender({ items: [{ id: 1 }, { id: 2 }, { id: 3 }], loadingMore: false })

    expect(screen.getAllByTestId('row')).toHaveLength(3)
    expect(screen.queryByTestId('row-placeholder')).toBeNull()
  })
})
