import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SideSheetButton.vue'

describe('sideSheetButton.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component, { slots: { default: 'Click me' } }).html()).toMatchSnapshot()
  })
})
