import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './PreviewBadge.vue'

describe('previewBadge.vue', () => {
  const h = createHarness()

  it('renders properly', () => {
    const { html } = h.render(Component)
    expect(html()).toMatchSnapshot()
  })
})
