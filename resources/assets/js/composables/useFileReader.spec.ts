import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { useFileReader } from './useFileReader'

describe('useFileReader', () => {
  createHarness()

  it('reads a file as data URL', async () => {
    const { readAsDataUrl } = useFileReader()
    const callback = vi.fn()

    const file = new File(['hello'], 'test.txt', { type: 'text/plain' })
    readAsDataUrl(file, callback)

    await vi.waitFor(() => {
      expect(callback).toHaveBeenCalledOnce()
      expect(callback.mock.calls[0][0]).toContain('data:')
    })
  })
})
