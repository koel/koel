import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarSection.vue'

describe('SidebarSection', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })

  it('renders header and default slots', () => {
    h.render(Component, {
      slots: {
        header: '<h3>Title</h3>',
        default: '<ul><li>Item</li></ul>',
      },
    })
    screen.getByText('Title')
    screen.getByText('Item')
  })
})
