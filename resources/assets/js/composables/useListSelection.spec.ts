import { describe, expect, it } from 'vite-plus/test'
import { reactive, ref } from 'vue'
import { useListSelection } from './useListSelection'

describe('useListSelection', () => {
  const createItem = (id: string) => reactive({ item: { id }, selected: false })

  const setup = () => {
    const items = ref([createItem('a'), createItem('b'), createItem('c'), createItem('d')])
    const selection = useListSelection(items)
    return { items, ...selection }
  }

  it('selects an item', () => {
    const { items, select, isSelected } = setup()
    select(items.value[0])

    expect(isSelected(items.value[0])).toBe(true)
    expect(items.value[0].selected).toBe(true)
  })

  it('deselects an item via toggle', () => {
    const { items, select, toggleSelected, isSelected } = setup()
    select(items.value[0])
    toggleSelected(items.value[0])

    expect(isSelected(items.value[0])).toBe(false)
  })

  it('selects all items', () => {
    const { items, selectAll, selected } = setup()
    selectAll()

    expect(selected.value).toHaveLength(4)
    expect(items.value.every(i => i.selected)).toBe(true)
  })

  it('deselects all items', () => {
    const { items, selectAll, deselectAll, selected } = setup()
    selectAll()
    deselectAll()

    expect(selected.value).toHaveLength(0)
    expect(items.value.every(i => !i.selected)).toBe(true)
  })

  it('toggles selection', () => {
    const { items, toggleSelected, isSelected } = setup()
    toggleSelected(items.value[1])
    expect(isSelected(items.value[1])).toBe(true)

    toggleSelected(items.value[1])
    expect(isSelected(items.value[1])).toBe(false)
  })

  it('selects a range between two items', () => {
    const { items, selectBetween, selected } = setup()
    selectBetween(items.value[0], items.value[2])

    expect(selected.value).toHaveLength(3)
    expect(items.value[0].selected).toBe(true)
    expect(items.value[1].selected).toBe(true)
    expect(items.value[2].selected).toBe(true)
    expect(items.value[3].selected).toBe(false)
  })

  it('selects range regardless of order', () => {
    const { items, selectBetween, selected } = setup()
    selectBetween(items.value[2], items.value[0])

    expect(selected.value).toHaveLength(3)
  })

  it('detects contiguous selected range', () => {
    const { items, select, inSelectedRange } = setup()
    select(items.value[0])
    select(items.value[1])
    select(items.value[2])

    expect(inSelectedRange(items.value[1])).toBe(true)
  })

  it('rejects non-contiguous range', () => {
    const { items, select, inSelectedRange } = setup()
    select(items.value[0])
    // skip items.value[1]
    select(items.value[2])

    expect(inSelectedRange(items.value[0])).toBe(false)
  })

  it('rejects unselected items in range check', () => {
    const { items, inSelectedRange } = setup()
    expect(inSelectedRange(items.value[0])).toBe(false)
  })

  it('reapplies selection after list mutation', () => {
    const { items, select, reapplySelection } = setup()
    select(items.value[0])
    select(items.value[2])

    // Simulate a list rebuild (e.g., re-sort)
    items.value.forEach(i => (i.selected = false))
    reapplySelection()

    expect(items.value[0].selected).toBe(true)
    expect(items.value[1].selected).toBe(false)
    expect(items.value[2].selected).toBe(true)
  })

  it('tracks lastSelected', () => {
    const { items, select, lastSelected } = setup()
    select(items.value[2])
    expect(lastSelected.value).toBe(items.value[2])
  })

  it('selectAllWithKeyboard requires ctrl/meta key', () => {
    const { selectAllWithKeyboard, selected } = setup()

    selectAllWithKeyboard({ ctrlKey: false, metaKey: false } as KeyboardEvent)
    expect(selected.value).toHaveLength(0)

    selectAllWithKeyboard({ ctrlKey: true, metaKey: false } as KeyboardEvent)
    expect(selected.value).toHaveLength(4)
  })

  it('works with a function idPath', () => {
    const items = ref([reactive({ name: 'x', selected: false }), reactive({ name: 'y', selected: false })])
    const { select, isSelected } = useListSelection(items, s => s.name)
    select(items.value[0])

    expect(isSelected(items.value[0])).toBe(true)
  })
})
