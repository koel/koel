import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { defineComponent } from 'vue'
import Component from './VirtualGridScroller.vue'

describe('virtualGridScroller', () => {
  const h = createHarness()

  afterEach(() => vi.unstubAllGlobals())

  const createItems = (count: number) => Array.from({ length: count }, (_, i) => ({ id: `id-${i}`, name: `Item ${i}` }))

  const stubResizeObserver = () => {
    const observers: { callback: ResizeObserverCallback; targets: Element[] }[] = []

    vi.stubGlobal(
      'ResizeObserver',
      vi.fn().mockImplementation(function (callback: ResizeObserverCallback) {
        const observer = { callback, targets: [] as Element[] }
        observers.push(observer)

        return {
          observe: (target: Element) => observer.targets.push(target),
          unobserve: vi.fn(),
          disconnect: vi.fn(),
        }
      }),
    )

    return observers
  }

  it('does not crash with empty items', async () => {
    h.render(Component, {
      props: { items: [], minItemWidth: 200 },
    })

    await h.tick(2)
    expect(document.body.innerHTML).toBeTruthy()
  })

  it('renders items via scoped slot after measuring', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100)

    const Wrapper = defineComponent({
      components: { VirtualGridScroller: Component },
      setup() {
        const items = createItems(3)
        return { items }
      },
      template: `
        <VirtualGridScroller :items="items" :min-item-width="200">
          <template #default="{ item }">
            <div data-testid="grid-item">{{ item.name }}</div>
          </template>
        </VirtualGridScroller>
      `,
    })

    h.render(Wrapper)
    await h.tick(3)

    expect(screen.getAllByTestId('grid-item')).toHaveLength(3)
  })

  it('follows the height of the rendered items', async () => {
    const observers = stubResizeObserver()

    const offsetHeightMock = vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100)

    const Wrapper = defineComponent({
      components: { VirtualGridScroller: Component },
      setup: () => ({ items: createItems(3) }),
      template: `
        <VirtualGridScroller :items="items" :min-item-width="200">
          <template #default="{ item }">
            <div data-testid="grid-item">{{ item.name }}</div>
          </template>
        </VirtualGridScroller>
      `,
    })

    h.render(Wrapper)
    await h.tick(3)

    const firstItem = screen.getAllByTestId('grid-item')[0]
    const itemObserver = observers.find(observer => observer.targets.includes(firstItem))!

    offsetHeightMock.mockReturnValue(300)
    itemObserver.callback([{ target: firstItem } as unknown as ResizeObserverEntry], {} as ResizeObserver)
    await h.tick()

    expect(screen.getByTestId('virtual-grid-height').style.height).toBe('900px')
  })

  it('asks for more items once shrinking items no longer fill the scroller', async () => {
    const observers = stubResizeObserver()

    const offsetHeightMock = vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100)

    const { emitted } = h.render(Component, {
      props: { items: createItems(3), minItemWidth: 200 },
      slots: { default: '<div data-testid="grid-item" />' },
    })

    await h.tick(3)

    expect(emitted('scrolled-to-end')).toBeUndefined()

    const firstItem = screen.getAllByTestId('grid-item')[0]
    const itemObserver = observers.find(observer => observer.targets.includes(firstItem))!

    offsetHeightMock.mockReturnValue(20)
    itemObserver.callback([{ target: firstItem } as unknown as ResizeObserverEntry], {} as ResizeObserver)
    await h.tick(2)

    expect(emitted('scrolled-to-end')).toHaveLength(1)
  })

  it('asks for more items with two screens of content left', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100)

    const { container, emitted } = h.render(Component, {
      props: { items: createItems(3), minItemWidth: 200 },
      slots: { default: '<div data-testid="grid-item" />' },
    })

    await h.tick(3)

    const scroller = container.querySelector<HTMLElement>('.virtual-grid-scroller')!
    vi.spyOn(scroller, 'scrollHeight', 'get').mockReturnValue(5000)
    vi.spyOn(scroller, 'clientHeight', 'get').mockReturnValue(1000)

    scroller.scrollTop = 1900
    await h.trigger(scroller, 'scroll')
    await new Promise(resolve => requestAnimationFrame(resolve))

    expect(emitted('scrolled-to-end')).toBeUndefined()

    scroller.scrollTop = 2000
    await h.trigger(scroller, 'scroll')
    await new Promise(resolve => requestAnimationFrame(resolve))

    expect(emitted('scrolled-to-end')).toHaveLength(1)
  })

  it('shows placeholders after the items while loading more', async () => {
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(100)

    const { rerender } = h.render(Component, {
      props: { items: createItems(3), minItemWidth: 200, loadingMore: true },
      slots: {
        default: '<div data-testid="grid-item" />',
        placeholder: '<div data-testid="grid-placeholder" />',
      },
    })

    await h.tick(3)

    expect(screen.getAllByTestId('grid-item')).toHaveLength(3)
    expect(screen.getAllByTestId('grid-placeholder').length).toBeGreaterThan(0)

    await rerender({ items: createItems(6), loadingMore: false })
    await h.tick(2)

    expect(screen.getAllByTestId('grid-item')).toHaveLength(6)
    expect(screen.queryByTestId('grid-placeholder')).toBeNull()
  })
})
