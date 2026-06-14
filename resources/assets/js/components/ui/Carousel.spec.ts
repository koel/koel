import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { defineComponent, h as createElement, nextTick, provide, ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { BlockActionsHostKey } from '@/config/symbols'
import Component from './Carousel.vue'

const setOverflow = async (scroller: HTMLDivElement, clientWidth: number, scrollWidth: number) => {
  Object.defineProperty(scroller, 'clientWidth', { value: clientWidth, configurable: true })
  Object.defineProperty(scroller, 'scrollWidth', { value: scrollWidth, configurable: true })
  window.dispatchEvent(new Event('resize'))
  await nextTick()
}

describe('carousel.vue', () => {
  const h = createHarness()

  it('renders the default slot content', () => {
    h.render(Component, {
      slots: { default: '<div data-testid="card">Card</div>' },
    })

    screen.getByTestId('card')
  })

  it('hides the scroll buttons when the track fits within the scroller', () => {
    h.render(Component, {
      slots: { default: '<div data-testid="card">Card</div>' },
    })

    expect(screen.queryByRole('button', { name: 'Scroll left' })).toBeNull()
    expect(screen.queryByRole('button', { name: 'Scroll right' })).toBeNull()
  })

  it('shows the scroll buttons when the track overflows the scroller', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    await setOverflow(scroller, 800, 3200)

    screen.getByRole('button', { name: 'Scroll left' })
    screen.getByRole('button', { name: 'Scroll right' })
  })

  it('slides right by the scroller width when the right chevron is clicked', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    const spy = vi.fn()
    scroller.scrollTo = spy as unknown as typeof scroller.scrollTo
    Object.defineProperty(scroller, 'scrollLeft', { value: 0, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    await h.user.click(screen.getByRole('button', { name: 'Scroll right' }))

    expect(spy).toHaveBeenCalledWith({ left: 800, behavior: 'smooth' })
  })

  it('slides left by the scroller width when the left chevron is clicked', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    const spy = vi.fn()
    scroller.scrollTo = spy as unknown as typeof scroller.scrollTo
    Object.defineProperty(scroller, 'scrollLeft', { value: 1600, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    await h.user.click(screen.getByRole('button', { name: 'Scroll left' }))

    expect(spy).toHaveBeenCalledWith({ left: 800, behavior: 'smooth' })
  })

  it('teleports the scroll buttons into a provided actions host', async () => {
    const Wrapper = defineComponent({
      setup() {
        const host = ref<HTMLElement | null>(null)
        provide(BlockActionsHostKey, host)
        return () =>
          createElement('div', [
            createElement('div', { ref: el => (host.value = el as HTMLElement), 'data-testid': 'host' }),
            createElement(Component, null, { default: () => createElement('div', 'Card') }),
          ])
      },
    })

    const { container } = h.render(Wrapper)
    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    await setOverflow(scroller, 800, 3200)

    const host = screen.getByTestId('host')
    expect(host.querySelector('button[title="Scroll left"]')).not.toBeNull()
    expect(host.querySelector('button[title="Scroll right"]')).not.toBeNull()
    // The inline <nav> must not coexist with the teleported buttons.
    expect(container.querySelector('nav button[title="Scroll left"]')).toBeNull()
  })
})
