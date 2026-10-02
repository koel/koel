import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ImageCropper.vue'

describe('imageCropper.vue', () => {
  const h = createHarness()

  it('renders outside of its host, which would clip it', () => {
    const { container } = h.render(Component, {
      props: { source: 'data:image/png;base64,abc' },
    })

    expect(container.textContent).toBe('')
    screen.getByText('Crop')
  })
})
