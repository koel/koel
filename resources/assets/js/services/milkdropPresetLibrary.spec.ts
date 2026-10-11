import 'fake-indexeddb/auto'
import { describe, expect, it, vi } from 'vite-plus/test'
import { milkdropPresetLibrary } from './milkdropPresetLibrary'

const makeFile = (name: string, content: string) => {
  const file = new File([content], name)
  Object.defineProperty(file, 'text', { value: async () => content })

  return file
}

describe('milkdropPresetLibrary', () => {
  it('imports MilkDrop presets and ignores other files', async () => {
    const imported = await milkdropPresetLibrary.importFiles([
      makeFile('Aurora.milk', '[preset00]\nzoom=1.01'),
      makeFile('cover.jpg', 'not a preset'),
    ])

    expect(imported.map(({ name }) => name)).toEqual(['Aurora'])

    const stored = await milkdropPresetLibrary.getAll()
    expect(stored.find(({ name }) => name === 'Aurora')?.content).toBe('[preset00]\nzoom=1.01')
    expect(stored.some(({ name }) => name === 'cover')).toBe(false)
  })

  it('replaces a preset imported again under the same name', async () => {
    await milkdropPresetLibrary.importFiles([makeFile('Twice.milk', 'first')])
    await milkdropPresetLibrary.importFiles([makeFile('Twice.milk', 'second')])

    const stored = (await milkdropPresetLibrary.getAll()).filter(({ name }) => name === 'Twice')
    expect(stored.map(({ content }) => content)).toEqual(['second'])
  })

  it('tells listeners about newly imported presets', async () => {
    const listener = vi.fn()
    const stopListening = milkdropPresetLibrary.onImport(listener)

    await milkdropPresetLibrary.importFiles([makeFile('Nova.milk', 'nova')])
    stopListening()
    await milkdropPresetLibrary.importFiles([makeFile('Late.milk', 'late')])

    expect(listener).toHaveBeenCalledTimes(1)
    expect(listener).toHaveBeenCalledWith([expect.objectContaining({ name: 'Nova', content: 'nova' })])
  })
})
