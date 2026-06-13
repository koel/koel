import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { defineComponent, h as createElement, inject } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { SortableItemKey } from '@/config/symbols'
import Component from './SortableListItem.vue'

const ContextProbe = defineComponent({
  setup() {
    const sortable = inject(SortableItemKey, null)
    return () =>
      createElement('button', {
        'data-testid': 'probe',
        onDragstart: (event: DragEvent) => sortable?.onHeaderDragStart(event),
      })
  },
})

const fire = (type: string, target: EventTarget, init: Record<string, unknown> = {}) => {
  const event = new Event(type, { bubbles: true, cancelable: true })
  for (const [key, value] of Object.entries(init)) {
    Object.defineProperty(event, key, { value, configurable: true })
  }
  target.dispatchEvent(event)
  return event
}

const stubRect = (el: HTMLElement) => {
  el.getBoundingClientRect = () =>
    ({
      top: 100,
      bottom: 200,
      height: 100,
      left: 0,
      right: 500,
      width: 500,
      x: 0,
      y: 100,
      toJSON: () => ({}),
    }) as DOMRect
}

describe('sortableListItem.vue', () => {
  const h = createHarness()

  it('renders the slot content', () => {
    h.render(Component, {
      props: { id: 'a', isDragging: false },
      slots: { default: '<div data-testid="body">body</div>' },
    })

    screen.getByTestId('body')
  })

  it('provides a context that surfaces dragstart with the wrapper element', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'a', isDragging: false },
      slots: { default: () => createElement(ContextProbe) },
    })

    fire('dragstart', screen.getByTestId('probe'))

    const wrapper = container.querySelector('.sortable-list-item')
    const [id, wrapperArg, eventArg] = emitted().dragstart[0] as [string, HTMLElement, Event]
    expect(id).toBe('a')
    expect(wrapperArg).toBe(wrapper)
    expect(eventArg).toBeInstanceOf(Event)
  })

  it('emits dragover with id when a drag passes over the wrapper', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'b', isDragging: false },
    })

    const wrapper = container.querySelector<HTMLElement>('.sortable-list-item')!
    stubRect(wrapper)
    fire('dragover', wrapper, { clientY: 120 })

    const [id] = emitted().dragover[0] as [string, Event]
    expect(id).toBe('b')
  })

  it('emits drop with id when a drop event fires', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'c', isDragging: false },
    })

    fire('drop', container.querySelector<HTMLElement>('.sortable-list-item')!)

    expect(emitted().drop[0]).toEqual(['c'])
  })

  it('applies the dragging modifier class when isDragging is true', () => {
    const { container } = h.render(Component, {
      props: { id: 'a', isDragging: true },
    })

    expect(container.querySelector('.sortable-list-item')!.classList.contains('sortable-list-item--dragging')).toBe(
      true,
    )
  })
})
