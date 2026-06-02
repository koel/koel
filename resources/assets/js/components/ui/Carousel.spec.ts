import { describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Carousel.vue'

describe('carousel.vue', () => {
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

  it('omits the refresh button when no onRefresh prop is passed', () => {
    h.render(Component, { slots: { header: 'Top', default: '<div>Card</div>' } })

    expect(screen.queryByRole('button', { name: 'Refresh' })).toBeNull()
  })

  it('calls onRefresh when the refresh button is clicked', async () => {
    const onRefresh = vi.fn().mockResolvedValue(undefined)
    h.render(Component, {
      props: { onRefresh },
      slots: { header: 'Top', default: '<div>Card</div>' },
    })

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(onRefresh).toHaveBeenCalled()
  })

  it('disables the refresh button while onRefresh is in flight', async () => {
    let resolve!: () => void
    const onRefresh = vi.fn(() => new Promise<void>(r => (resolve = r)))

    h.render(Component, {
      props: { onRefresh },
      slots: { header: 'Top', default: '<div>Card</div>' },
    })

    const button = screen.getByRole<HTMLButtonElement>('button', { name: 'Refresh' })
    expect(button.disabled).toBe(false)
    expect(button.classList.contains('animate-spin')).toBe(false)

    const clickPromise = h.user.click(button)

    await waitFor(() => {
      expect(button.disabled).toBe(true)
      expect(button.classList.contains('animate-spin')).toBe(true)
    })

    resolve()
    await clickPromise

    await waitFor(() => {
      expect(button.disabled).toBe(false)
      expect(button.classList.contains('animate-spin')).toBe(false)
    })
  })
})
