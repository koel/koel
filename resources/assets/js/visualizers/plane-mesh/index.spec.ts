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
    setClearAlpha = vi.fn()

    constructor () {
      renderers.push(this)
    }
  },
}))

vi.mock('@/visualizers/analyser', () => ({
  configureAnalyser: () => ({ frequencyBinCount: 512, getByteFrequencyData: vi.fn() }),
}))

vi.mock('@/visualizers/frameLoop', () => ({
  startFrameLoop: () => vi.fn(),
}))

import { init } from './index'

describe('plane-mesh visualizer', () => {
  createHarness()

  it('releases its WebGL context on destroy', () => {
    const destroy = init(document.createElement('div'))

    destroy()

    expect(renderers.at(-1)!.forceContextLoss).toHaveBeenCalled()
  })
})
