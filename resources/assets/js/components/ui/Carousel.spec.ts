import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Carousel.vue'

const setOverflow = async (scroller: HTMLDivElement, clientWidth: number, scrollWidth: number) => {
  Object.defineProperty(scroller, 'clientWidth', { value: clientWidth, configurable: true })
  Object.defineProperty(scroller, 'scrollWidth', { value: scrollWidth, configurable: true })
  window.dispatchEvent(new Event('resize'))
  await nextTick()
}

describe('carousel.vue', () => {
  const h = createHarness()

  it('hides the scroll buttons when the track fits within the scroller', () => {
    h.render(Component, {
      slots: { default: '<div data-testid="card">Card</div>' },
    })

    expect(screen.queryByRole('button', { name: 'Scroll left' })).toBeNull()
    expect(screen.queryByRole('button', { name: 'Scroll right' })).toBeNull()
  })

  it('only offers scrolling right at the start', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
    Object.defineProperty(scroller, 'scrollLeft', { value: 0, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    expect(screen.queryByRole('button', { name: 'Scroll left' })).toBeNull()
    screen.getByRole('button', { name: 'Scroll right' })
  })

  it('only offers scrolling left at the end', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
    Object.defineProperty(scroller, 'scrollLeft', { value: 2400, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    screen.getByRole('button', { name: 'Scroll left' })
    expect(screen.queryByRole('button', { name: 'Scroll right' })).toBeNull()
  })

  it('offers scrolling left once scrolled away from the start', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
    Object.defineProperty(scroller, 'scrollLeft', { value: 0, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    scroller.scrollLeft = 800
    scroller.dispatchEvent(new Event('scroll'))
    await nextTick()

    screen.getByRole('button', { name: 'Scroll left' })
  })

  it('moves focus to the other scroll button when the focused one hides at the end', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
    Object.defineProperty(scroller, 'scrollLeft', { value: 1600, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    screen.getByRole('button', { name: 'Scroll right' }).focus()

    scroller.scrollLeft = 2400
    scroller.dispatchEvent(new Event('scroll'))
    await nextTick()
    await nextTick()

    expect(screen.queryByRole('button', { name: 'Scroll right' })).toBeNull()
    expect(document.activeElement).toBe(screen.getByRole('button', { name: 'Scroll left' }))
  })

  it('slides right by the scroller width when the right chevron is clicked', async () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
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

    const scroller = container.querySelector<HTMLDivElement>('.home-carousel')!
    const spy = vi.fn()
    scroller.scrollTo = spy as unknown as typeof scroller.scrollTo
    Object.defineProperty(scroller, 'scrollLeft', { value: 1600, configurable: true, writable: true })
    await setOverflow(scroller, 800, 3200)

    await h.user.click(screen.getByRole('button', { name: 'Scroll left' }))

    expect(spy).toHaveBeenCalledWith({ left: 800, behavior: 'smooth' })
  })
})
