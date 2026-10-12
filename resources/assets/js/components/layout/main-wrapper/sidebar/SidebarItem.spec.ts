import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarItem.vue'

describe('sidebarItem', () => {
  const h = createHarness()

  const renderComponent = (disabledReason: string | null = null) => {
    return h.render(Component, {
      props: {
        href: '#',
        disabledReason,
      },
      slots: {
        default: 'Home',
      },
    })
  }

  it('navigates and toggles sidebar on single click', async () => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent()

    await h.user.click(screen.getByText('Home'))

    await waitFor(
      () => {
        expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
      },
      { timeout: 500 },
    )
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

  it('emits dblclick on double click', async () => {
    const { emitted } = renderComponent()

    await h.user.dblClick(screen.getByText('Home'))

    await waitFor(() => {
      expect(emitted().dblclick).toBeTruthy()
    })
  })
})
