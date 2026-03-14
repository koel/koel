import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

const { mockPreferences } = vi.hoisted(() => ({
  mockPreferences: {
    equalizer: {
      name: 'Default',
      preamp: 0,
      gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    } as any,
    init: vi.fn(),
    initialized: { value: true },
    state: {},
    setupProxy: vi.fn(),
    set: vi.fn(),
    get: vi.fn(),
    update: vi.fn(),
  },
}))

vi.mock('@/stores/preferenceStore', () => ({
  preferenceStore: mockPreferences,
}))

import { equalizerStore } from './equalizerStore'

describe('equalizerStore', () => {
  createHarness({
    beforeEach: () => {
      mockPreferences.equalizer = {
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }
    },
  })

  it('finds preset by name', () => {
    const preset = equalizerStore.getPresetByName('Rock')
    expect(preset).toBeDefined()
    expect(preset!.name).toBe('Rock')
  })

  it('returns undefined for unknown preset', () => {
    expect(equalizerStore.getPresetByName('NonExistent')).toBeUndefined()
  })

  it('returns named preset config from preferences', () => {
    mockPreferences.equalizer = { name: 'Classical', preamp: 0, gains: [] }
    const config = equalizerStore.getConfig()
    expect(config.name).toBe('Classical')
  })

  it('returns Default preset when name is unknown', () => {
    mockPreferences.equalizer = { name: 'DoesNotExist', preamp: 0, gains: [] }
    const config = equalizerStore.getConfig()
    expect(config.name).toBe('Default')
  })

  it('returns custom preset directly when isCustom', () => {
    const custom = { name: null, preamp: 5, gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
    mockPreferences.equalizer = custom
    const config = equalizerStore.getConfig()
    expect(config.name).toBeNull()
    expect(config.preamp).toBe(5)
    expect(config.gains).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
  })

  it('detects custom preset correctly', () => {
    expect(
      equalizerStore.isCustom({
        name: null,
        preamp: 3,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }),
    ).toBe(true)
  })

  it('rejects preset with name as non-custom', () => {
    expect(
      equalizerStore.isCustom({
        name: 'Rock',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }),
    ).toBe(false)
  })

  it('rejects preset with wrong gains count', () => {
    expect(
      equalizerStore.isCustom({
        name: null,
        preamp: 0,
        gains: [0, 0, 0],
      }),
    ).toBe(false)
  })

  it('rejects non-object as non-custom', () => {
    expect(equalizerStore.isCustom(null)).toBe(false)
    expect(equalizerStore.isCustom('string')).toBe(false)
  })

  it('saves named preset config', () => {
    equalizerStore.saveConfig('Rock', 0, [])
    expect(mockPreferences.equalizer.name).toBe('Rock')
  })

  it('saves custom config when name is null', () => {
    equalizerStore.saveConfig(null, 7, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
    expect(mockPreferences.equalizer.name).toBeNull()
    expect(mockPreferences.equalizer.preamp).toBe(7)
    expect(mockPreferences.equalizer.gains).toEqual([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
  })
})
