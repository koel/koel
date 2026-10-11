import { afterEach, beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { audioService } from '@/services/audioService'
import { startFrameLoop } from './frameLoop'

describe('startFrameLoop', () => {
  const h = createHarness()

  let queuedFrames: Map<number, FrameRequestCallback>
  let nextFrameId: number

  const runNextFrame = () => {
    const [[id, callback]] = queuedFrames
    queuedFrames.delete(id)
    callback(0)
  }

  beforeEach(() => {
    queuedFrames = new Map()
    nextFrameId = 0

    vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
      queuedFrames.set(++nextFrameId, callback)
      return nextFrameId
    })

    vi.stubGlobal('cancelAnimationFrame', (id: number) => queuedFrames.delete(id))
  })

  afterEach(() => vi.unstubAllGlobals())

  it('renders on every frame until stopped', () => {
    h.setReadOnlyProperty(audioService, 'element', { paused: false })
    const renderFrame = vi.fn()
    const stop = startFrameLoop(renderFrame)

    runNextFrame()
    runNextFrame()
    stop()

    expect(renderFrame).toHaveBeenCalledTimes(2)
    expect(queuedFrames.size).toBe(0)
  })

  it('renders only the first frame while playback is paused', () => {
    h.setReadOnlyProperty(audioService, 'element', { paused: true })
    const renderFrame = vi.fn()
    const stop = startFrameLoop(renderFrame)

    runNextFrame()
    runNextFrame()
    runNextFrame()
    stop()

    expect(renderFrame).toHaveBeenCalledTimes(1)
  })
})
