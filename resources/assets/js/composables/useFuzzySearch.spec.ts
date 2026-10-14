import Fuse from 'fuse.js'
import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { useFuzzySearch } from './useFuzzySearch'

describe('useFuzzySearch', () => {
  const h = createHarness()

  const items = [
    { name: 'Alice', role: 'admin' },
    { name: 'Bob', role: 'user' },
    { name: 'Charlie', role: 'admin' },
  ]

  it('returns all items when query is empty', () => {
    const { search } = useFuzzySearch(items, ['name'])
    expect(search('')).toEqual(items)
  })

  it('returns all items when query is null', () => {
    const { search } = useFuzzySearch(items, ['name'])
    expect(search(null)).toEqual(items)
  })

  it('returns all items when query is whitespace', () => {
    const { search } = useFuzzySearch(items, ['name'])
    expect(search('   ')).toEqual(items)
  })

  it('finds matching items by name', () => {
    const { search } = useFuzzySearch(items, ['name'])
    const results = search('Alice')
    expect(results.length).toBeGreaterThanOrEqual(1)
    expect(results[0].name).toBe('Alice')
  })

  it('finds items added to a ref list in place', async () => {
    const refItems = ref([...items])
    const { search } = useFuzzySearch(refItems, ['name'])

    refItems.value.push({ name: 'Diana', role: 'editor' })
    await h.tick()

    expect(search('Diana').map(item => item.name)).toEqual(['Diana'])
  })

  it('updates documents with setDocuments', () => {
    const { search, setDocuments } = useFuzzySearch(items, ['name'])

    setDocuments([{ name: 'Diana', role: 'editor' }])

    const results = search('Diana')
    expect(results).toHaveLength(1)
    expect(results[0].name).toBe('Diana')
  })

  it('works with ref items and returns ref value on empty query', async () => {
    const refItems = ref(items)
    const { search } = useFuzzySearch(refItems, ['name'])

    expect(search('')).toEqual(items)
  })

  it('reacts to ref changes', async () => {
    const refItems = ref([...items])
    const { search } = useFuzzySearch(refItems, ['name'])

    refItems.value = [{ name: 'Zara', role: 'user' }]
    await h.tick()

    const results = search('Zara')
    expect(results).toHaveLength(1)
    expect(results[0].name).toBe('Zara')
  })

  it('builds the search index only once a search needs it', async () => {
    const refItems = ref([...items])
    const { search } = useFuzzySearch(refItems, ['name'])
    const setCollectionSpy = vi.spyOn(Fuse.prototype, 'setCollection')

    refItems.value.push({ name: 'Diana', role: 'editor' })
    await h.tick()
    search('')

    expect(setCollectionSpy).not.toHaveBeenCalled()

    search('Diana')
    search('Bob')

    expect(setCollectionSpy).toHaveBeenCalledOnce()

    setCollectionSpy.mockRestore()
  })
})
