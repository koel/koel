import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarItem.vue'

describe('sidebarItem', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      props: {
        href: '#',
      },
      slots: {
        default: 'Home',
      },
    })
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

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

  it('emits dblclick on double click', async () => {
    const { emitted } = renderComponent()

    await h.user.dblClick(screen.getByText('Home'))

    await waitFor(() => {
      expect(emitted().dblclick).toBeTruthy()
    })
  })
})
