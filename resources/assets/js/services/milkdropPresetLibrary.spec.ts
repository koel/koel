import 'fake-indexeddb/auto'
import { describe, expect, it } from 'vite-plus/test'
import { milkdropPresetLibrary } from './milkdropPresetLibrary'

const makeFile = (name: string, content: string) => {
  const file = new File([content], name)
  Object.defineProperty(file, 'text', { value: async () => content })

  return file
}

describe('milkdropPresetLibrary', () => {
  it('imports MilkDrop presets and counts the files it skips', async () => {
    const result = await milkdropPresetLibrary.importFiles([
      makeFile('Aurora.milk', '[preset00]\nzoom=1.01'),
      makeFile('cover.jpg', 'not a preset'),
    ])

    expect(result).toEqual({ importedNames: ['Aurora'], skippedCount: 1 })
    expect(await milkdropPresetLibrary.getNames()).toContain('Aurora')
    expect(await milkdropPresetLibrary.getContent('Aurora')).toBe('[preset00]\nzoom=1.01')
  })

  it('replaces a preset imported again under the same name', async () => {
    await milkdropPresetLibrary.importFiles([makeFile('Twice.milk', 'first')])
    await milkdropPresetLibrary.importFiles([makeFile('Twice.milk', 'second')])

    expect(await milkdropPresetLibrary.getContent('Twice')).toBe('second')
  })

  it('removes a preset', async () => {
    await milkdropPresetLibrary.importFiles([makeFile('Gone.milk', 'bye')])

    await milkdropPresetLibrary.remove('Gone')

    expect(await milkdropPresetLibrary.getNames()).not.toContain('Gone')
    expect(await milkdropPresetLibrary.getContent('Gone')).toBeUndefined()
  })
})
