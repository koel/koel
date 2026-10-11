import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { milkdropPresetLibrary } from '@/services/milkdropPresetLibrary'
import { milkdropPresetStore, RANDOM_PRESET } from './milkdropPresetStore'

describe('milkdropPresetStore', () => {
  const h = createHarness()

  it('remembers the selected preset in this browser', () => {
    milkdropPresetStore.select('Aurora')

    expect(milkdropPresetStore.state.selected).toBe('Aurora')
    expect(JSON.parse(localStorage.getItem('milkdrop-preset')!)).toBe('Aurora')
  })

  it.each<[string, string, string]>([
    ['keeps Random', RANDOM_PRESET, RANDOM_PRESET],
    ['switches a chosen preset to the first import', 'Aurora', 'Nova'],
  ])('refreshes the preset names after an import and %s', async (_, selectedBefore, selectedAfter) => {
    milkdropPresetStore.state.selected = selectedBefore
    h.mock(milkdropPresetLibrary, 'importFiles').mockResolvedValue({
      importedNames: ['Nova', 'Orbit'],
      skippedCount: 0,
    })
    h.mock(milkdropPresetLibrary, 'getNames').mockResolvedValue(['Aurora', 'Nova', 'Orbit'])

    await milkdropPresetStore.importFiles([])

    expect(milkdropPresetStore.state.names).toEqual(['Aurora', 'Nova', 'Orbit'])
    expect(milkdropPresetStore.state.selected).toBe(selectedAfter)
  })

  it('goes back to Random when the chosen preset is removed', async () => {
    milkdropPresetStore.state.selected = 'Aurora'
    const removeMock = h.mock(milkdropPresetLibrary, 'remove')
    h.mock(milkdropPresetLibrary, 'getNames').mockResolvedValue(['Nova'])

    await milkdropPresetStore.remove('Aurora')

    expect(removeMock).toHaveBeenCalledWith('Aurora')
    expect(milkdropPresetStore.state.names).toEqual(['Nova'])
    expect(milkdropPresetStore.state.selected).toBe(RANDOM_PRESET)
  })
})
