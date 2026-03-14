import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ImageCropper.vue'

describe('imageCropper.vue', () => {
  const h = createHarness()

  it('renders crop and cancel buttons', () => {
    h.render(Component, {
      props: { source: 'data:image/png;base64,abc' },
    })

    screen.getByText('Crop')
    screen.getByText('Cancel')
  })

  it('emits cancel on cancel click', async () => {
    const { emitted } = h.render(Component, {
      props: { source: 'data:image/png;base64,abc' },
    })

    await h.user.click(screen.getByText('Cancel'))
    expect(emitted().cancel).toBeTruthy()
  })
})
