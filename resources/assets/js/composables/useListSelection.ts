import type { Reactive, Ref } from 'vue'

type Selectable = Reactive<Record<any, any> & { selected: boolean }>

export const useListSelection = (items: Ref<Selectable[]>) => {
  const select = (item: Selectable) => (item.selected = true)
  const deselect = (item: Selectable) => (item.selected = false)
  const selectAll = () => items.value.forEach(item => (item.selected = true))
  const deselectAll = () => items.value.forEach(item => (item.selected = false))
  const toggleSelected = (item: Selectable) => (item.selected = !item.selected)

  const selectBetween = (first: Selectable, second: Selectable, indexFinderFn: (selectable: Selectable) => number) => {
    // const firstIndex = Math.max(0, findIndex(items.value, row => row.playable.id === first.playable.id))
    // const secondIndex = Math.max(0, findIndex(rows.value, row => row.playable.id === second.playable.id))
    const firstIndex = Math.max(0, indexFinderFn(first))
    const secondIndex = Math.max(0, indexFinderFn(second))
    const indexes = [firstIndex, secondIndex]
    indexes.sort((a, b) => a - b)

    for (let i = indexes[0]; i <= indexes[1]; ++i) {
      items.value[i].selected = true
    }
  }

  return {
    select,
    deselect,
    selectAll,
    deselectAll,
    clearSelection: deselectAll,
    toggleSelected,
    selectBetween,
  }
}
