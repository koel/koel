import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

const renderers = vi.hoisted<{ forceContextLoss: () => void }[]>(() => [])

vi.mock('three', async importOriginal => ({
  ...(await importOriginal<typeof import('three')>()),
  WebGLRenderer: class {
    domElement = document.createElement('canvas')
    dispose = vi.fn()
    forceContextLoss = vi.fn()
    setPixelRatio = vi.fn()
    setSize = vi.fn()

    constructor () {
      renderers.push(this)
    }
  },
}))

import SceneInit from './SceneInit'

describe('SceneInit', () => {
  createHarness()

  it('releases its WebGL context on destroy', () => {
    new SceneInit(document.createElement('div')).destroy()

    expect(renderers.at(-1)!.forceContextLoss).toHaveBeenCalled()
  })
})
