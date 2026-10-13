import { describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarItem.vue'

describe('sidebarItem', () => {
  const h = createHarness()

  const renderComponent = (disabledReason: string | null = null, onDblclick?: () => void, active = false) => {
    return h.render(Component, {
      props: {
        href: '#',
        active,
        disabledReason,
        onDblclick,
      },
      slots: {
        default: 'Home',
      },
    })
  }

  it('navigates and toggles sidebar right away on single click', async () => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent()

    await h.user.click(screen.getByText('Home'))

    expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it('navigates after a short wait on single click when double click is handled', async () => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent(null, () => {})

    await h.user.click(screen.getByText('Home'))

    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')

    await waitFor(
      () => {
        expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
      },
      { timeout: 500 },
    )
  })

  it.each(['metaKey', 'ctrlKey', 'shiftKey'])('leaves a click with %s to the browser', async modifier => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent()

    const click = new MouseEvent('click', { bubbles: true, cancelable: true, [modifier]: true })
    screen.getByRole('link').dispatchEvent(click)

    expect(click.defaultPrevented).toBe(false)
    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it('does not navigate after being removed during the double-click wait', async () => {
    vi.useFakeTimers()
    const mock = h.mock(eventBus, 'emit')
    const { unmount } = renderComponent(null, vi.fn())

    screen.getByRole('link').dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }))
    unmount()
    vi.advanceTimersByTime(200)
    vi.useRealTimers()

    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it.each([1, 2])('leaves a click with mouse button %i to the browser', button => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent()

    const click = new MouseEvent('click', { bubbles: true, cancelable: true, button })
    screen.getByRole('link').dispatchEvent(click)

    expect(click.defaultPrevented).toBe(false)
    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it('stays in place and explains why when disabled', async () => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent('Not now')

    const link = screen.getByText('Home').closest('a')!
    await h.user.click(link)
    await new Promise(resolve => setTimeout(resolve, 200))

    expect(link.getAttribute('aria-disabled')).toBe('true')
    expect(link.title).toBe('Not now')
    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it('calls the double click handler and skips navigation on double click', async () => {
    const mock = h.mock(eventBus, 'emit')
    const onDblclick = vi.fn()
    renderComponent(null, onDblclick)

    await h.user.dblClick(screen.getByText('Home'))
    await new Promise(resolve => setTimeout(resolve, 200))

    expect(onDblclick).toHaveBeenCalled()
    expect(mock).not.toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })

  it.each([
    [true, 'page'],
    [false, null],
  ])('marks the link as the current page only when active (active: %s)', (active, ariaCurrent) => {
    renderComponent(null, undefined, active)

    expect(screen.getByRole('link').getAttribute('aria-current')).toBe(ariaCurrent)
  })
})
