import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { faHome } from '@fortawesome/free-solid-svg-icons'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarItem.vue'

describe('sidebarItem.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    return h.render(Component, {
      props: {
        icon: faHome,
        href: '#',
        screen: 'Home',
      },
      slots: {
        default: 'Home',
      },
    })
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())

  it('emits the sidebar toggle event when clicked', async () => {
    const mock = h.mock(eventBus, 'emit')
    renderComponent()
    await h.user.click(screen.getByTestId('sidebar-item'))

    expect(mock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })
})
