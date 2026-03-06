import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Tabs.vue'

describe('tabs.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, { slots: { default: 'Tabs content' } }).html()).toMatchSnapshot()
  })
})
