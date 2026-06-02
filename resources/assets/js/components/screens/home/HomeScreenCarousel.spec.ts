import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './HomeScreenCarousel.vue'

describe('homeScreenCarousel.vue', () => {
  const h = createHarness()

  it('renders the header slot and both scroll buttons', () => {
    h.render(Component, {
      slots: {
        header: 'Most Played',
        default: '<div data-testid="card">Card</div>',
      },
    })

    screen.getByText('Most Played')
    screen.getByRole('button', { name: 'Scroll left' })
    screen.getByRole('button', { name: 'Scroll right' })
    screen.getByTestId('card')
  })

  it('slides right by the scroller width when the right chevron is clicked', async () => {
    const { container } = h.render(Component, {
      slots: { header: 'Top', default: '<div>Card</div>' },
    })

    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    const spy = vi.fn()
    scroller.scrollTo = spy as unknown as typeof scroller.scrollTo
    Object.defineProperty(scroller, 'clientWidth', { value: 800, configurable: true })
    Object.defineProperty(scroller, 'scrollWidth', { value: 3200, configurable: true })
    Object.defineProperty(scroller, 'scrollLeft', { value: 0, configurable: true, writable: true })

    await h.user.click(screen.getByRole('button', { name: 'Scroll right' }))

    expect(spy).toHaveBeenCalledWith({ left: 800, behavior: 'smooth' })
  })

  it('slides left by the scroller width when the left chevron is clicked', async () => {
    const { container } = h.render(Component, {
      slots: { header: 'Top', default: '<div>Card</div>' },
    })

    const scroller = container.querySelector('.home-carousel') as HTMLDivElement
    const spy = vi.fn()
    scroller.scrollTo = spy as unknown as typeof scroller.scrollTo
    Object.defineProperty(scroller, 'clientWidth', { value: 800, configurable: true })
    Object.defineProperty(scroller, 'scrollWidth', { value: 3200, configurable: true })
    Object.defineProperty(scroller, 'scrollLeft', { value: 1600, configurable: true, writable: true })

    await h.user.click(screen.getByRole('button', { name: 'Scroll left' }))

    expect(spy).toHaveBeenCalledWith({ left: 800, behavior: 'smooth' })
  })
})
