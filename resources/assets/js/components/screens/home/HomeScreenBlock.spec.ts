import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { defineComponent, h as createElement, provide } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { ReorderableItemKey } from '@/config/symbols'
import Component from './HomeScreenBlock.vue'

const withReorderable = (onHeaderDragStart: (event: DragEvent) => void) =>
  defineComponent({
    setup(_, { slots }) {
      provide(ReorderableItemKey, { onHeaderDragStart })
      return () => createElement(Component, null, slots)
    },
  })

describe('HomeScreenBlock', () => {
  const h = createHarness()

  it('renders header, actions, and default slots', () => {
    h.render(Component, {
      slots: {
        header: 'Recently Played',
        actions: '<button data-testid="action">act</button>',
        default: '<p>Song list</p>',
      },
    })

    screen.getByText('Recently Played')
    screen.getByTestId('action')
    screen.getByText('Song list')
  })

  it('leaves the title non-draggable when no reorderable context is provided', () => {
    const { container } = h.render(Component, {
      slots: { header: 'Top Albums' },
    })

    const heading = container.querySelector<HTMLHeadingElement>('h3')!
    expect(heading.getAttribute('draggable')).toBe('false')
    expect(heading.classList.contains('cursor-grab')).toBe(false)
  })

  it('makes the title draggable and forwards dragstart when a reorderable context is injected', () => {
    const spy = vi.fn()
    const Wrapper = withReorderable(spy)

    const { container } = h.render(Wrapper, {
      slots: { header: 'Top Albums' },
    })

    const heading = container.querySelector<HTMLHeadingElement>('h3')!
    expect(heading.getAttribute('draggable')).toBe('true')
    expect(heading.classList.contains('cursor-grab')).toBe(true)

    heading.dispatchEvent(new Event('dragstart', { bubbles: true }))
    expect(spy).toHaveBeenCalledOnce()
  })
})
