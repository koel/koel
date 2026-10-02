import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarSection.vue'

describe('SidebarSection', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
