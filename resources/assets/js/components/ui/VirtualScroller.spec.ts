import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './VirtualScroller.vue'

describe('virtualScroller.vue', () => {
  const h = createHarness()

  it('renders items via scoped slot', () => {
    const items = [
      { id: 1, name: 'Item 1' },
      { id: 2, name: 'Item 2' },
    ]

    const { container } = h.render(Component, {
      props: { items, itemHeight: 40 },
      slots: {
        default: (props: { item: { name: string } }) => props.item.name,
      },
    })

    expect(container.querySelector('.virtual-scroller')).toBeTruthy()
  })
})
