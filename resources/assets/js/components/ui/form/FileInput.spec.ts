import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './FileInput.vue'

describe('fileInput.vue', () => {
  const h = createHarness()

  it('renders with default slot text', () => {
    const { getByText } = h.render(Component)
    getByText(/Select a file/)
  })

  it('renders with custom slot text', () => {
    const { getByText } = h.render(Component, {
      slots: { default: 'Upload image' },
    })

    getByText('Upload image')
  })

  it('renders a file input', () => {
    const { container } = h.render(Component)
    expect(container.querySelector('input[type="file"]')).not.toBeNull()
  })
})
