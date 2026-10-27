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

  it('deselects items hidden from the list too', () => {
    const { items, select, deselectAll, reapplySelection } = setup()
    const [first, second] = items.value
    select(second)

    items.value = items.value.filter(item => item !== second)
    deselectAll()
    items.value = [first, second]
    reapplySelection()

    expect(second.selected).toBe(false)
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

  const arrowKey = (key: 'ArrowDown' | 'ArrowUp', shiftKey = false) => new KeyboardEvent('keydown', { key, shiftKey })

  const selectedFlags = (items: ReturnType<typeof setup>['items']) => items.value.map(item => item.selected)

  it('moves the single selection with the arrow keys', () => {
    const { items, select, moveSelectionWithArrowKey } = setup()
    select(items.value[0])
    select(items.value[1])

    expect(moveSelectionWithArrowKey(arrowKey('ArrowDown'))).toBe(2)
    expect(selectedFlags(items)).toEqual([false, false, true, false])

    expect(moveSelectionWithArrowKey(arrowKey('ArrowUp'))).toBe(1)
    expect(selectedFlags(items)).toEqual([false, true, false, false])
  })

  it('starts from the first row going down and the last row going up when nothing is selected', () => {
    const { items, moveSelectionWithArrowKey, deselectAll } = setup()

    expect(moveSelectionWithArrowKey(arrowKey('ArrowDown'))).toBe(0)
    expect(selectedFlags(items)).toEqual([true, false, false, false])

    deselectAll()

    expect(moveSelectionWithArrowKey(arrowKey('ArrowUp'))).toBe(3)
    expect(selectedFlags(items)).toEqual([false, false, false, true])
  })

  it('extends and shrinks the selection from the anchor with Shift', () => {
    const { items, toggleSelected, moveSelectionWithArrowKey } = setup()
    toggleSelected(items.value[1])

    moveSelectionWithArrowKey(arrowKey('ArrowDown', true))
    moveSelectionWithArrowKey(arrowKey('ArrowDown', true))
    expect(selectedFlags(items)).toEqual([false, true, true, true])

    moveSelectionWithArrowKey(arrowKey('ArrowUp', true))
    moveSelectionWithArrowKey(arrowKey('ArrowUp', true))
    moveSelectionWithArrowKey(arrowKey('ArrowUp', true))
    expect(selectedFlags(items)).toEqual([true, true, false, false])
  })

  it('extends a Shift-clicked range from the clicked row', () => {
    const { items, toggleSelected, selectBetween, moveSelectionWithArrowKey } = setup()
    toggleSelected(items.value[3])
    selectBetween(items.value[3], items.value[1])

    moveSelectionWithArrowKey(arrowKey('ArrowUp', true))

    expect(selectedFlags(items)).toEqual([true, true, true, true])
  })

  it('stops at the ends of the list', () => {
    const { items, select, moveSelectionWithArrowKey } = setup()
    select(items.value[3])

    expect(moveSelectionWithArrowKey(arrowKey('ArrowDown'))).toBeNull()
    expect(selectedFlags(items)).toEqual([false, false, false, true])
  })

  it('works with a function idPath', () => {
    const items = ref([reactive({ name: 'x', selected: false }), reactive({ name: 'y', selected: false })])
    const { select, isSelected } = useListSelection(items, s => s.name)
    select(items.value[0])

    expect(isSelected(items.value[0])).toBe(true)
  })
})
