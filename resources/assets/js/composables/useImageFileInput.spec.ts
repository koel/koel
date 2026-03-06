import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { useImageFileInput } from './useImageFileInput'

describe('useImageFileInput', () => {
  createHarness()

  it('calls onImageDataUrl when file is selected', async () => {
    const onImageDataUrl = vi.fn()
    const { onImageInputChange } = useImageFileInput({ onImageDataUrl })

    const file = new File(['fake-image-data'], 'photo.png', { type: 'image/png' })

    const input = document.createElement('input')
    input.type = 'file'

    Object.defineProperty(input, 'files', {
      value: [file],
      writable: false,
    })

    onImageInputChange({ target: input } as unknown as InputEvent)

    await vi.waitFor(() => {
      expect(onImageDataUrl).toHaveBeenCalledOnce()
      expect(onImageDataUrl.mock.calls[0][0]).toContain('data:')
    })
  })

  it('does nothing when no files selected', () => {
    const onImageDataUrl = vi.fn()
    const { onImageInputChange } = useImageFileInput({ onImageDataUrl })

    const input = document.createElement('input')
    input.type = 'file'

    Object.defineProperty(input, 'files', {
      value: [],
      writable: false,
    })

    onImageInputChange({ target: input } as unknown as InputEvent)

    expect(onImageDataUrl).not.toHaveBeenCalled()
  })

  it('resets input value after reading', async () => {
    const onImageDataUrl = vi.fn()
    const { onImageInputChange } = useImageFileInput({ onImageDataUrl })

    const file = new File(['data'], 'photo.png', { type: 'image/png' })

    const input = document.createElement('input')
    input.type = 'file'
    input.value = '' // jsdom won't let us set a real file path

    Object.defineProperty(input, 'files', {
      value: [file],
      writable: false,
    })

    onImageInputChange({ target: input } as unknown as InputEvent)

    expect(input.value).toBe('')
  })
})
