import { describe, expect, it } from 'vite-plus/test'
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

  it('finds items with fuzzy matching', () => {
    const { search } = useFuzzySearch(items, ['name'])
    const results = search('chrl')
    expect(results.length).toBeGreaterThanOrEqual(1)
    expect(results[0].name).toBe('Charlie')
  })

  it('searches across multiple keys', () => {
    const { search } = useFuzzySearch(items, ['name', 'role'])
    const results = search('admin')
    expect(results.length).toBeGreaterThanOrEqual(2)
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
})
