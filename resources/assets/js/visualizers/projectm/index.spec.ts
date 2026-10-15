import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/stores/milkdropPresetStore', () => ({
  RANDOM_PRESET: '',
  milkdropPresetStore: {
    state: { names: [], selected: '' },
    load: vi.fn(),
  },
}))

vi.mock('@/visualizers/analyser', () => ({
  configureAnalyser: () => ({ getFloatTimeDomainData: vi.fn() }),
}))

vi.mock('@/visualizers/frameLoop', () => ({
  startFrameLoop: () => vi.fn(),
}))

import { init } from './index'

describe('projectM visualizer', () => {
  createHarness()

  afterEach(() => vi.restoreAllMocks())

  it('releases its WebGL context on destroy', async () => {
    const loseContext = vi.fn()

    vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({
      getExtension: () => ({ loseContext }),
    } as unknown as WebGL2RenderingContext)

    const appendToHead = document.head.appendChild.bind(document.head)

    vi.spyOn(document.head, 'appendChild').mockImplementation(<T extends Node>(node: T) => {
      appendToHead(node)
      ;(node as unknown as HTMLScriptElement).onload?.(new Event('load'))

      return node
    })

    window.KoelProjectM = async () => ({
      cwrap: () => vi.fn().mockReturnValue(0),
      _malloc: () => 0,
      _free: vi.fn(),
      HEAPF32: new Float32Array(),
    })

    const container = document.createElement('div')
    document.body.appendChild(container)

    const destroy = await init(container)

    destroy()

    expect(loseContext).toHaveBeenCalled()
  })
})
