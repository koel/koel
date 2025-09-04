import type { Reactive, Ref } from 'vue'
import { computed, ref } from 'vue'
import { findIndex, findLastIndex, get } from 'lodash'

type Selectable<T> = Reactive<T & { selected: boolean }>

export const useListSelection = <T> (
  selectables: Ref<Array<Selectable<T>>>,
  idPath: string | ((s: Selectable<T>) => string) = 'item.id',
) => {
  const lastSelected = ref<Selectable<T> | null>(null)

  // Keep track of selected items by their ID, so that when the list is updated (e.g., items are removed or added,
  // the list is sorted, or infinite loading triggered), we can reapply the selection.
  const selectedIds = new Set()

  const resolveIdPath = (selectable: Selectable<T>) => typeof idPath === 'function' ? idPath(selectable) : idPath

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
  const deselectAll = () => selectables.value.forEach(deselect)
  const isSelected = (item: Selectable<T>) => item.selected
  const selected = computed(() => selectables.value.filter(isSelected))

  const toggleSelected = (selectable: Selectable<T>) => {
    if (isSelected(selectable)) {
      deselect(selectable)
    } else {
      select(selectable)
    }

    lastSelected.value = selectable
  }

  const selectBetween = (first: Selectable<T>, second: Selectable<T>) => {
    const firstIndex = Math.max(
      0,
      findIndex(selectables.value, s => get(s, resolveIdPath(s)) === get(first, resolveIdPath(first))),
    )

    const secondIndex = Math.max(
      0,
      findIndex(selectables.value, s => get(s, resolveIdPath(s)) === get(second, resolveIdPath(second))),
    )

    const indexes = [firstIndex, secondIndex]
    indexes.sort((a, b) => a - b)

    for (let i = indexes[0]; i <= indexes[1]; ++i) {
      select(selectables.value[i])
    }
  }

  const inSelectedRange = (selectable: Selectable<T>) => {
    if (!isSelected(selectable)) {
      return false
    }

    const index = findIndex(
      selectables.value,
      s => get(s, resolveIdPath(s)) === get(selectable, resolveIdPath(selectable)),
    )

    const firstSelectedIndex = Math.max(0, findIndex(selectables.value, isSelected))
    const lastSelectedIndex = Math.max(0, findLastIndex(selectables.value, isSelected))

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

  return {
    select,
    isSelected,
    selectAll,
    selectAllWithKeyboard,
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
