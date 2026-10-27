import type { Reactive, Ref } from 'vue'
import { computed, ref } from 'vue'
import { get } from 'lodash-es'

type Selectable<T> = Reactive<T & { selected: boolean }>

export const useListSelection = <T>(
  selectables: Ref<Array<Selectable<T>>>,
  idPath: string | ((s: Selectable<T>) => string) = 'item.id',
) => {
  const lastSelected = ref<Selectable<T> | null>(null)
  let rangeAnchor: Selectable<T> | null = null

  // Keep track of selected items by their ID, so that when the list is updated (e.g., items are removed or added,
  // the list is sorted, or infinite loading triggered), we can reapply the selection.
  const selectedIds = new Set()

  const resolveIdPath = (selectable: Selectable<T>) => (typeof idPath === 'function' ? idPath(selectable) : idPath)

  const indexOf = (selectable: Selectable<T>) =>
    selectables.value.findIndex(
      candidate => get(candidate, resolveIdPath(candidate)) === get(selectable, resolveIdPath(selectable)),
    )

  const select = (selectable: Selectable<T>) => {
    selectable.selected = true
    lastSelected.value = selectable
    selectedIds.add(get(selectable, resolveIdPath(selectable)))
  }

  const deselect = (selectable: Selectable<T>) => {
    selectable.selected = false
    lastSelected.value = selectable // we still consider it as "last selected" so that we can "select between"
    selectedIds.delete(get(selectable, resolveIdPath(selectable)))
  }

  const selectAll = () => selectables.value.forEach(select)

  const deselectAll = () => {
    selectables.value.forEach(deselect)
    selectedIds.clear()
  }

  const isSelected = (item: Selectable<T>) => item.selected
  const selected = computed(() => selectables.value.filter(isSelected))

  const toggleSelected = (selectable: Selectable<T>) => {
    if (isSelected(selectable)) {
      deselect(selectable)
    } else {
      select(selectable)
    }

    lastSelected.value = selectable
    rangeAnchor = selectable
  }

  const selectBetween = (first: Selectable<T>, second: Selectable<T>) => {
    const firstIndex = Math.max(
      0,
      selectables.value.findIndex(s => get(s, resolveIdPath(s)) === get(first, resolveIdPath(first))),
    )

    const secondIndex = Math.max(
      0,
      selectables.value.findIndex(s => get(s, resolveIdPath(s)) === get(second, resolveIdPath(second))),
    )

    const indexes = [firstIndex, secondIndex]
    indexes.sort((a, b) => a - b)

    for (let i = indexes[0]; i <= indexes[1]; ++i) {
      select(selectables.value[i])
    }

    lastSelected.value = second
  }

  const inSelectedRange = (selectable: Selectable<T>) => {
    if (!isSelected(selectable)) {
      return false
    }

    const index = selectables.value.findIndex(
      s => get(s, resolveIdPath(s)) === get(selectable, resolveIdPath(selectable)),
    )

    const firstSelectedIndex = Math.max(0, selectables.value.findIndex(isSelected))
    const lastSelectedIndex = Math.max(0, selectables.value.findLastIndex(isSelected))

    if (index < firstSelectedIndex || index > lastSelectedIndex) {
      return false
    }

    for (let i = firstSelectedIndex; i <= lastSelectedIndex; ++i) {
      if (!isSelected(selectables.value[i])) {
        return false
      }
    }

    return true
  }

  const reapplySelection = () => {
    selectables.value.forEach(selectable => {
      // Don't use select() here, as it will set lastSelected and cause other side effects.
      selectable.selected = selectedIds.has(get(selectable, resolveIdPath(selectable)))
    })
  }

  const selectAllWithKeyboard = (event: KeyboardEvent) => {
    if (event.ctrlKey || event.metaKey) {
      selectAll()
    }
  }

  const resolveRangeAnchor = (fallback: Selectable<T>) =>
    rangeAnchor && indexOf(rangeAnchor) !== -1 ? rangeAnchor : fallback

  /**
   * Moves the selection one row up or down, or extends/shrinks it from the anchor when Shift is held.
   * Returns the index of the row moved to, or null if there's nowhere to move.
   */
  const moveSelectionWithArrowKey = (event: KeyboardEvent) => {
    if (event.altKey || event.ctrlKey || event.metaKey) {
      return null
    }

    const step = event.key === 'ArrowDown' ? 1 : -1
    const currentIndex = selected.value.length && lastSelected.value ? indexOf(lastSelected.value) : -1
    const lastIndex = selectables.value.length - 1

    let targetIndex = currentIndex + step

    if (currentIndex === -1) {
      targetIndex = step === 1 ? 0 : lastIndex
    }

    if (targetIndex < 0 || targetIndex > lastIndex) {
      return null
    }

    const target = selectables.value[targetIndex]
    const anchor = event.shiftKey && currentIndex !== -1 ? resolveRangeAnchor(lastSelected.value!) : target

    deselectAll()
    selectBetween(anchor, target)

    rangeAnchor = anchor

    return targetIndex
  }

  return {
    select,
    isSelected,
    selectAll,
    selectAllWithKeyboard,
    moveSelectionWithArrowKey,
    deselectAll,
    clearSelection: deselectAll,
    toggleSelected,
    selectBetween,
    reapplySelection,
    inSelectedRange,
    selected,
    lastSelected,
  }
}
