import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { preferenceStore } from '@/stores/preferenceStore'
import { equalizerStore } from '@/stores/equalizerStore'

describe('equalizerStore', () => {
  const h = createHarness({
    beforeEach: () => {
      preferenceStore.current_equalizer_preset = {
        name: 'Default',
        preamp: 0,
        gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
      }
      preferenceStore.equalizer_presets = []
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
    preferenceStore.current_equalizer_preset = { name: 'Classical', preamp: 0, gains: [] }
    expect(equalizerStore.getConfig().name).toBe('Classical')
  })

  it('returns Default preset when name is unknown', () => {
    preferenceStore.current_equalizer_preset = { name: 'DoesNotExist', preamp: 0, gains: [] }
    expect(equalizerStore.getConfig().name).toBe('Default')
  })

  it('returns modified preset directly when name is null', () => {
    preferenceStore.current_equalizer_preset = {
      name: null,
      preamp: 5,
      gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    }
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
    const postMock = h.mock(http, 'post').mockResolvedValueOnce(serverPreset)

    const created = await equalizerStore.saveCustomPreset('My Bass', 3, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])

    expect(postMock).toHaveBeenCalledWith('me/equalizer-presets', {
      name: 'My Bass',
      preamp: 3,
      gains: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    })
    expect(created).toEqual(serverPreset)
    expect(equalizerStore.state.customPresets).toEqual([serverPreset])
  })

  it('deletes a custom preset by id via DELETE', async () => {
    const serverPreset = { id: '01HSERVER', name: 'Tmp', preamp: 0, gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0] }
    h.mock(http, 'post').mockResolvedValueOnce(serverPreset)
    const deleteMock = h.mock(http, 'delete').mockResolvedValueOnce(undefined)

    const created = await equalizerStore.saveCustomPreset('Tmp', 0, [0, 0, 0, 0, 0, 0, 0, 0, 0, 0])
    await equalizerStore.deleteCustomPreset(created.id!)

    expect(deleteMock).toHaveBeenCalledWith(`me/equalizer-presets/${created.id}`)
    expect(equalizerStore.state.customPresets).toHaveLength(0)
  })

  it('saves last-applied config (named preset)', () => {
    const rock = equalizerStore.getBuiltInPresetByName('Rock')!
    equalizerStore.saveConfig(rock, 0, rock.gains)
    expect(preferenceStore.current_equalizer_preset.name).toBe('Rock')
  })

  it('saves last-applied config (modified)', () => {
    equalizerStore.saveConfig(null, 7, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10])
    expect(preferenceStore.current_equalizer_preset.name).toBeNull()
    expect(preferenceStore.current_equalizer_preset.preamp).toBe(7)
  })
})
