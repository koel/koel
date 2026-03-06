import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './GridListView.vue'

describe('gridListView', () => {
  const h = createHarness()

  it('renders with thumbnails mode by default', () => {
    const { container } = h.render(Component, {
      slots: { default: '<div>Card</div>' },
    })

    expect(container.querySelector('.as-thumbnails')).toBeTruthy()
  })

  it('renders with list mode', () => {
    const { container } = h.render(Component, {
      props: { viewMode: 'list' },
      slots: { default: '<div>Row</div>' },
    })

    expect(container.querySelector('.as-list')).toBeTruthy()
  })

  it('renders slot content', () => {
    const { getByText } = h.render(Component, {
      slots: { default: '<div>My Content</div>' },
    })

    getByText('My Content')
  })
})
