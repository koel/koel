import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarSectionHeader.vue'

describe('SidebarSectionHeader', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component, {
      slots: { default: 'Library' },
    })
    expect(html()).toMatchSnapshot()
  })
})
