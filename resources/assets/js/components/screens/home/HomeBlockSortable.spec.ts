import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { defineComponent, h as createElement, inject } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { HomeBlockSortableKey } from '@/config/symbols'
import Component from './HomeBlockSortable.vue'

const ContextProbe = defineComponent({
  setup() {
    const sortable = inject(HomeBlockSortableKey, null)
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

describe('homeBlockSortable.vue', () => {
  const h = createHarness()

  it('renders the slot content', () => {
    h.render(Component, {
      props: { id: 'random-songs', isDragging: false },
      slots: { default: '<div data-testid="block-body">block</div>' },
    })

    screen.getByTestId('block-body')
  })

  it('provides a sortable context that surfaces dragstart with the wrapper element', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'random-songs', isDragging: false },
      slots: { default: () => createElement(ContextProbe) },
    })

    fire('dragstart', screen.getByTestId('probe'))

    const wrapper = container.querySelector('.home-block-sortable')
    const [id, wrapperArg, eventArg] = emitted().dragstart[0] as [string, HTMLElement, Event]
    expect(id).toBe('random-songs')
    expect(wrapperArg).toBe(wrapper)
    expect(eventArg).toBeInstanceOf(Event)
  })

  it('emits dragover with id when a drag passes over the wrapper', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'most-played-songs', isDragging: false },
    })

    const wrapper = container.querySelector<HTMLElement>('.home-block-sortable')!
    stubRect(wrapper)
    fire('dragover', wrapper, { clientY: 120, dataTransfer: { dropEffect: 'none' } })

    const [id] = emitted().dragover[0] as [string, Event]
    expect(id).toBe('most-played-songs')
  })

  it('emits drop with id when a drop event fires', () => {
    const { container, emitted } = h.render(Component, {
      props: { id: 'top-albums', isDragging: false },
    })

    const wrapper = container.querySelector<HTMLElement>('.home-block-sortable')!
    fire('drop', wrapper)

    expect(emitted().drop[0]).toEqual(['top-albums'])
  })

  it('applies the dragging modifier class when isDragging is true', () => {
    const { container } = h.render(Component, {
      props: { id: 'random-songs', isDragging: true },
    })

    expect(container.querySelector('.home-block-sortable')!.classList.contains('home-block-sortable--dragging')).toBe(
      true,
    )
  })
})
