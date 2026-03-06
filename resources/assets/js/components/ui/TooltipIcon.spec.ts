import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './TooltipIcon.vue'

describe('TooltipIcon', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component, { props: { title: 'Help text' } })
    expect(html()).toMatchSnapshot()
  })
})
