import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './DropZone.vue'

vi.mock('@/composables/useUpload', () => ({
  useUpload: () => ({
    allowsUpload: { value: true },
    mediaPathSetUp: { value: true },
    handleDropEvent: vi.fn(),
  }),
}))

describe('dropZone.vue', () => {
  const h = createHarness()

  it('renders drop zone with text', () => {
    h.render(Component)
    screen.getByText('Drop to upload')
  })

  it('emits close on dragleave', async () => {
    const { emitted } = h.render(Component)
    const dropZone = screen.getByText('Drop to upload').closest('.drop-zone')!
    await dropZone.dispatchEvent(new Event('dragleave'))

    expect(emitted().close).toBeTruthy()
  })
})
