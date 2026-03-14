import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TabPanelContainer.vue'

describe('tabPanelContainer.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, { slots: { default: 'Content' } }).html()).toMatchSnapshot()
  })
})
