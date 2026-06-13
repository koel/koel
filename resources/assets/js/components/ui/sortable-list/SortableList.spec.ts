import { describe, expect, it } from 'vite-plus/test'
import { defineComponent, h as createElement, inject, nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { SortableItemKey } from '@/config/symbols'
import Component from './SortableList.vue'

const ITEMS = [{ id: 'a' }, { id: 'b' }, { id: 'c' }, { id: 'd' }]

const Probe = defineComponent({
  props: { label: { type: String, required: true } },
  setup(props) {
    const sortable = inject(SortableItemKey, null)
    return () =>
      createElement('div', {
        'data-testid': props.label,
        onDragstart: (event: DragEvent) => sortable?.onHeaderDragStart(event),
      })
  },
})

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

const itemIds = (container: Element) =>
  Array.from(container.querySelectorAll<HTMLElement>('.sortable-list-item__inner > [data-testid]')).map(
    el => el.dataset.testid!,
  )

describe('sortableList.vue', () => {
  const h = createHarness()

  const renderList = () =>
    h.render(Component, {
      props: { items: ITEMS },
      slots: { default: ({ item }) => createElement(Probe, { label: (item as { id: string }).id }) },
    })

  it('renders one item per input in canonical order', () => {
    const { container } = renderList()

    expect(itemIds(container)).toEqual(['a', 'b', 'c', 'd'])
  })

  it('emits reorder with the new id list when an item is dropped onto another', async () => {
    const { container, emitted } = renderList()
    const wrappers = Array.from(container.querySelectorAll<HTMLElement>('.sortable-list-item'))

    stubRect(wrappers[0], 0)
    stubRect(wrappers[2], 200)

    dispatch(wrappers[0].querySelector<HTMLElement>('[data-testid="a"]')!, 'dragstart')
    await nextTick()

    dispatch(wrappers[2], 'dragover', { clientY: 260 })
    dispatch(wrappers[2], 'drop')
    await nextTick()

    const events = emitted().reorder as unknown[] | undefined
    expect(events).toBeDefined()
    const [ids] = events![0] as [string[]]
    expect(ids.indexOf('a')).toBeGreaterThan(ids.indexOf('c'))
  })

  it('does not emit reorder when the drag is released without a drop', async () => {
    const { container, emitted } = renderList()
    const wrappers = Array.from(container.querySelectorAll<HTMLElement>('.sortable-list-item'))

    stubRect(wrappers[0], 0)
    stubRect(wrappers[2], 200)

    dispatch(wrappers[0].querySelector<HTMLElement>('[data-testid="a"]')!, 'dragstart')
    await nextTick()
    dispatch(wrappers[2], 'dragover', { clientY: 260 })
    await nextTick()
    dispatch(document, 'dragend')
    await nextTick()

    expect(emitted().reorder).toBeUndefined()
  })
})
