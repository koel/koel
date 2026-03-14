import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { isFullscreenSupported } from './supports'

describe('supports', () => {
  createHarness()

  it('checks fullscreen support', () => {
    // jsdom may or may not support fullscreen, just verify it returns a boolean
    expect(typeof isFullscreenSupported()).toBe('boolean')
  })
})
