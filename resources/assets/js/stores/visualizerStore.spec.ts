import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { visualizerStore } from './visualizerStore'

describe('visualizerStore', () => {
  createHarness()

  it('returns all visualizers', () => {
    const all = visualizerStore.all
    expect(all.length).toBeGreaterThanOrEqual(1)
    expect(all[0]).toHaveProperty('id')
    expect(all[0]).toHaveProperty('name')
    expect(all[0]).toHaveProperty('init')
  })

  it('finds visualizer by id', () => {
    const viz = visualizerStore.getVisualizerById('default')
    expect(viz).toBeDefined()
    expect(viz!.name).toBe('Color Pills')
  })

  it('returns undefined for unknown id', () => {
    expect(visualizerStore.getVisualizerById('nonexistent')).toBeUndefined()
  })

  it('includes known visualizers', () => {
    const ids = visualizerStore.all.map(v => v.id)
    expect(ids).toContain('default')
    expect(ids).toContain('waveform')
    expect(ids).toContain('asteroid')
  })
})
