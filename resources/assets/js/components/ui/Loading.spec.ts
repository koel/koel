import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Loading.vue'

describe('Loading', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
