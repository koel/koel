import { fireEvent, screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './ReorderBlocksModal.vue'

const blocks = [
  { id: 'recently-played-songs', label: 'Recently Played' },
  { id: 'recently-added-albums', label: 'Latest Albums' },
  { id: 'most-played-albums', label: 'Top Albums' },
  { id: 'random-songs', label: 'Random Songs' },
]

const dispatch = (target: EventTarget, type: string, init: Record<string, unknown> = {}) => {
  const event = new Event(type, { bubbles: true, cancelable: true })
  for (const [key, value] of Object.entries(init)) {
    Object.defineProperty(event, key, { value, configurable: true })
  }
  target.dispatchEvent(event)
  return event
}

const stubRect = (el: HTMLElement, top: number, height = 40) => {
  el.getBoundingClientRect = () =>
    ({
      top,
      bottom: top + height,
      height,
      left: 0,
      right: 300,
      width: 300,
      x: 0,
      y: top,
      toJSON: () => ({}),
    }) as DOMRect
}

const rowIds = (container: Element) =>
  Array.from(container.querySelectorAll<HTMLElement>('[draggable="true"]')).map(el => el.textContent?.trim() ?? '')

describe('ReorderBlocksModal', () => {
  const h = createHarness({
    beforeEach: () => {
      preferenceStore.temporary.home_blocks_order = []
    },
  })

  it('renders one row per block in canonical order when no preference is set', () => {
    h.render(Component, { props: { blocks } })

    blocks.forEach(b => screen.getByText(b.label))
  })

  it('renders rows in the order specified by preferenceStore.home_blocks_order', () => {
    preferenceStore.temporary.home_blocks_order = ['random-songs', 'recently-added-albums']

    const { container } = h.render(Component, { props: { blocks } })
    const labels = rowIds(container)

    expect(labels[0]).toBe('Random Songs')
    expect(labels[1]).toBe('Latest Albums')
  })

  it('appends blocks missing from the saved order at the canonical tail', () => {
    preferenceStore.temporary.home_blocks_order = ['random-songs']

    const { container } = h.render(Component, { props: { blocks } })
    const labels = rowIds(container)

    expect(labels[0]).toBe('Random Songs')
    // The remaining three keep their canonical relative order.
    expect(labels.slice(1)).toEqual(['Recently Played', 'Latest Albums', 'Top Albums'])
  })

  it('marks the dragged row with the opacity-40 modifier while a drag is in flight', async () => {
    const { container } = h.render(Component, { props: { blocks } })
    const rows = container.querySelectorAll<HTMLElement>('[draggable="true"]')

    dispatch(rows[0], 'dragstart', { dataTransfer: { effectAllowed: '' } })
    await nextTick()

    expect(rows[0].classList.contains('opacity-40')).toBe(true)
  })

  it('reorders the rendered rows reactively as the cursor hovers over a target', async () => {
    const { container } = h.render(Component, { props: { blocks } })
    const rows = Array.from(container.querySelectorAll<HTMLElement>('[draggable="true"]'))

    // Source at row 0 (Recently Played), target at row 2 (Top Albums).
    stubRect(rows[0], 0)
    stubRect(rows[2], 80)

    dispatch(rows[0], 'dragstart', { dataTransfer: { effectAllowed: '' } })
    // Lower half of the target → insert after.
    dispatch(rows[2], 'dragover', { clientY: 105 })
    await nextTick()

    // After the reorder, the source should sit after Top Albums in the DOM.
    const labels = rowIds(container)
    const sourceIdx = labels.indexOf('Recently Played')
    const targetIdx = labels.indexOf('Top Albums')
    expect(sourceIdx).toBeGreaterThan(targetIdx)
  })

  it('persists the current order to preferenceStore.home_blocks_order on dragend', async () => {
    const { container } = h.render(Component, { props: { blocks } })
    const rows = Array.from(container.querySelectorAll<HTMLElement>('[draggable="true"]'))

    stubRect(rows[0], 0)
    stubRect(rows[2], 80)

    dispatch(rows[0], 'dragstart', { dataTransfer: { effectAllowed: '' } })
    dispatch(rows[2], 'dragover', { clientY: 105 })
    await nextTick()
    dispatch(rows[0], 'dragend')

    const saved = preferenceStore.home_blocks_order
    const sourceIdx = saved.indexOf('recently-played-songs')
    const targetIdx = saved.indexOf('most-played-albums')
    expect(sourceIdx).toBeGreaterThan(targetIdx)
  })

  it('emits close when the Close button is clicked', async () => {
    const { emitted } = h.render(Component, { props: { blocks } })

    await fireEvent.click(screen.getByText('Close'))

    expect(emitted().close).toHaveLength(1)
  })

  it('emits close when Escape is pressed', async () => {
    const { emitted } = h.render(Component, { props: { blocks } })

    await fireEvent.keyDown(screen.getByTestId('reorder-blocks-modal'), { key: 'Escape' })

    expect(emitted().close).toHaveLength(1)
  })
})
