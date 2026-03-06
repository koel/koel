import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FooterButton.vue'

describe('footerButton.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, { slots: { default: 'Button' } }).html()).toMatchSnapshot()
  })
})
