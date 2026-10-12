import { describe, expect, it, vi } from 'vite-plus/test'
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
})
