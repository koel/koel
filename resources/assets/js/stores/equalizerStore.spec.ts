import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

const { mockPreferences, mockHttp } = vi.hoisted(() => ({
  mockPreferences: {
    current_equalizer_preset: {
      name: 'Default',
      preamp: 0,
      gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    } as any,
    equalizer_presets: [] as any[],
    init: vi.fn(),
    initialized: { value: true },
    state: {},
    setupProxy: vi.fn(),
    set: vi.fn(),
    get: vi.fn(),
    update: vi.fn(),
  },
  mockHttp: {
    post: vi.fn(),
    delete: vi.fn(),
  },
}))

vi.mock('@/stores/preferenceStore', () => ({
  preferenceStore: mockPreferences,
}))

vi.mock('@/services/http', () => ({
  http: mockHttp,
}))

import { equalizerStore } from './equalizerStore'

describe('equalizerStore', () => {
  createHarness({
    beforeEach: () => {
      mockPreferences.current_equalizer_preset = {
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }
      mockPreferences.equalizer_presets = []
      mockHttp.post.mockReset()
      mockHttp.delete.mockReset()
      equalizerStore.init()
    },
  })

  it('finds built-in preset by name', () => {
    const preset = equalizerStore.getBuiltInPresetByName('Rock')
    expect(preset).toBeDefined()
    expect(preset!.name).toBe('Rock')
  })

  it('returns undefined for unknown built-in preset', () => {
    expect(equalizerStore.getBuiltInPresetByName('NonExistent')).toBeUndefined()
  })

  it('returns named built-in config from preferences', () => {
    mockPreferences.current_equalizer_preset = { name: 'Classical', preamp: 0, gains: [] }
    expect(equalizerStore.getConfig().name).toBe('Classical')
  })

  it('returns Default preset when name is unknown', () => {
    mockPreferences.current_equalizer_preset = { name: 'DoesNotExist', preamp: 0, gains: [] }
    expect(equalizerStore.getConfig().name).toBe('Default')
  })

  it('returns modified preset directly when name is null', () => {
    const modified = { name: null, preamp: 5, gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10] }
    mockPreferences.current_equalizer_preset = modified
    const config = equalizerStore.getConfig()
    expect(config.name).toBeNull()
    expect(config.preamp).toBe(5)
  })

  it('isModified is true only when id and name are both null', () => {
    expect(equalizerStore.isModified({ name: null, preamp: 3, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0] })).toBe(true)
    expect(equalizerStore.isModified({ name: 'Rock', preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0] })).toBe(false)
    expect(
      equalizerStore.isModified({ id: '01J0', name: null, preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0] }),
    ).toBe(false)
  })

  it('isCustom is true when id is set', () => {
    expect(equalizerStore.isCustom({ id: '01J0', name: 'Mine', preamp: 0, gains: [] })).toBe(true)
    expect(equalizerStore.isCustom({ name: 'Rock', preamp: 0, gains: [] })).toBe(false)
  })

  it('isBuiltIn is true when no id and name matches a built-in', () => {
    expect(equalizerStore.isBuiltIn({ name: 'Rock', preamp: 0, gains: [] })).toBe(true)
    expect(equalizerStore.isBuiltIn({ id: '01J0', name: 'Rock', preamp: 0, gains: [] })).toBe(false)
    expect(equalizerStore.isBuiltIn({ name: 'NotABuiltIn', preamp: 0, gains: [] })).toBe(false)
  })

  it('saves a custom preset by POSTing and storing the server response', async () => {
    const serverPreset = {
      id: '01HSERVERMINTED0000000000',
      name: 'My Bass',
      preamp: 3,
      gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    }
    mockHttp.post.mockResolvedValueOnce(serverPreset)

    const created = await equalizerStore.saveCustomPreset('My Bass', 3, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])

    expect(mockHttp.post).toHaveBeenCalledWith('me/equalizer-presets', {
      name: 'My Bass',
      preamp: 3,
      gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    })
    expect(created).toEqual(serverPreset)
    expect(equalizerStore.state.customPresets).toEqual([serverPreset])
  })

  it('deletes a custom preset by id via DELETE', async () => {
    const serverPreset = { id: '01HSERVER', name: 'Tmp', preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0] }
    mockHttp.post.mockResolvedValueOnce(serverPreset)
    mockHttp.delete.mockResolvedValueOnce(undefined)

    const created = await equalizerStore.saveCustomPreset('Tmp', 0, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0])
    await equalizerStore.deleteCustomPreset(created.id!)

    expect(mockHttp.delete).toHaveBeenCalledWith(`me/equalizer-presets/${created.id}`)
    expect(equalizerStore.state.customPresets).toHaveLength(0)
  })

  it('saves last-applied config (named preset)', () => {
    const rock = equalizerStore.getBuiltInPresetByName('Rock')!
    equalizerStore.saveConfig(rock, 0, rock.gains)
    expect(mockPreferences.current_equalizer_preset.name).toBe('Rock')
  })

  it('saves last-applied config (modified)', () => {
    equalizerStore.saveConfig(null, 7, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
    expect(mockPreferences.current_equalizer_preset.name).toBeNull()
    expect(mockPreferences.current_equalizer_preset.preamp).toBe(7)
  })
})
