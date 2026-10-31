import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { useSelectableRows } from './useSelectableRows'

describe('useSelectableRows', () => {
  const setup = () => {
    const scrollToIndexIfHidden = vi.fn()
    const items = ref([{ id: 'a' }, { id: 'b' }, { id: 'c' }])
    const scroller = ref({ scrollToIndexIfHidden, getScrollerElement: () => null } as any)

    return { scrollToIndexIfHidden, ...useSelectableRows(items, scroller) }
  }

  const click = (options: MouseEventInit = {}) => new MouseEvent('click', { button: 0, ...options })

  it('selects only the clicked row', () => {
    const { rows, selectedItems, onRowClick } = setup()

    onRowClick(rows.value[0], click())
    onRowClick(rows.value[1], click())

    expect(selectedItems.value).toEqual([{ id: 'b' }])
  })

  it('toggles a row with Cmd/Ctrl', () => {
    const { rows, selectedItems, onRowClick } = setup()

    onRowClick(rows.value[0], click())
    onRowClick(rows.value[2], click({ metaKey: true }))
    onRowClick(rows.value[0], click({ ctrlKey: true }))

    expect(selectedItems.value).toEqual([{ id: 'c' }])
  })

  it('selects a range with Shift', () => {
    const { rows, selectedItems, onRowClick } = setup()

    onRowClick(rows.value[0], click())
    onRowClick(rows.value[2], click({ shiftKey: true }))

    expect(selectedItems.value).toHaveLength(3)
  })

  it('keeps the selection when right-clicking a selected row', async () => {
    const { rows, selectedItems, onRowClick, selectRowForContextMenu } = setup()

    onRowClick(rows.value[0], click())
    onRowClick(rows.value[1], click({ shiftKey: true }))
    await selectRowForContextMenu(rows.value[1])

    expect(selectedItems.value).toEqual([{ id: 'a' }, { id: 'b' }])
  })

  it('selects only the right-clicked row when it is not selected', async () => {
    const { rows, selectedItems, onRowClick, selectRowForContextMenu } = setup()

    onRowClick(rows.value[0], click())
    onRowClick(rows.value[1], click({ shiftKey: true }))
    await selectRowForContextMenu(rows.value[2])

    expect(selectedItems.value).toEqual([{ id: 'c' }])
  })

  it('moves the selection with arrow keys and scrolls to it', () => {
    const { rows, selectedItems, onRowClick, onArrowKey, scrollToIndexIfHidden } = setup()

    onRowClick(rows.value[0], click())
    onArrowKey(new KeyboardEvent('keydown', { key: 'ArrowDown' }))

    expect(selectedItems.value).toEqual([{ id: 'b' }])
    expect(scrollToIndexIfHidden).toHaveBeenCalledWith(1)
  })
})
