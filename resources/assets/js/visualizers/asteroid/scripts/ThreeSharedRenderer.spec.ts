import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('three', async importOriginal => ({
  ...(await importOriginal<typeof import('three')>()),
  WebGLRenderer: class {
    domElement = document.createElement('canvas')
    shadowMap = {}
    dispose = vi.fn()
    forceContextLoss = vi.fn()
    setPixelRatio = vi.fn()
    setSize = vi.fn()
  },
}))

import { ThreeSharedRenderer } from './ThreeSharedRenderer'

describe('ThreeSharedRenderer', () => {
  createHarness()

  it('releases its WebGL context on destroy', () => {
    const sharedRenderer = new ThreeSharedRenderer(document.createElement('div'))

    sharedRenderer.destroy()

    expect(sharedRenderer.renderer.forceContextLoss).toHaveBeenCalled()
  })
})
