import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ParagraphSkeleton.vue'

describe('ParagraphSkeleton', () => {
  const h = createHarness()

  it('renders default 4 lines', () => {
    const { container } = h.render(Component)
    expect(container.querySelectorAll('p')).toHaveLength(4)
  })

  it('renders custom number of lines', () => {
    const { container } = h.render(Component, { props: { lines: 6 } })
    expect(container.querySelectorAll('p')).toHaveLength(6)
  })
})
