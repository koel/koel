import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ScreenHeaderSkeleton.vue'

describe('ScreenHeaderSkeleton', () => {
  const h = createHarness()

  it('renders correctly', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
