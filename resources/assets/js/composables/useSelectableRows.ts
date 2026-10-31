import type { Reactive, Ref } from 'vue'
import isMobile from 'ismobilejs'
import { computed, nextTick, reactive, watch } from 'vue'
import { useListSelection } from '@/composables/useListSelection'

import type VirtualScroller from '@/components/ui/VirtualScroller.vue'

type Row<T> = { item: T }

export type SelectableRow<T> = Reactive<Row<T> & { selected: boolean }>

export const useSelectableRows = <T extends { id: string }>(
  items: Ref<T[]>,
  scroller: Ref<InstanceType<typeof VirtualScroller> | undefined>,
) => {
  const rows = computed(() => items.value.map(item => reactive({ item, selected: false })))

  const {
    selectAllWithKeyboard,
    moveSelectionWithArrowKey,
    selectBetween,
    clearSelection,
    toggleSelected,
    selected,
    lastSelected,
    reapplySelection,
  } = useListSelection<Row<T>>(rows)

  watch(rows, () => reapplySelection())

  const selectedItems = computed(() => selected.value.map(row => row.item))

  const onRowClick = (row: SelectableRow<T>, event: MouseEvent) => {
    if (isMobile.any) {
      toggleSelected(row)
      return
    }

    if (event.ctrlKey || event.metaKey) {
      toggleSelected(row)
      return
    }

    if (event.shiftKey && lastSelected.value) {
      selectBetween(lastSelected.value, row)
      return
    }

    clearSelection()
    toggleSelected(row)
  }

  const selectRowForContextMenu = async (row: SelectableRow<T>) => {
    if (row.selected) {
      return
    }

    clearSelection()
    toggleSelected(row)
    await nextTick()
  }

  const onArrowKey = (event: KeyboardEvent) => {
    const index = moveSelectionWithArrowKey(event)

    if (index === null) {
      return
    }

    scroller.value?.getScrollerElement()?.focus({ preventScroll: true })
    scroller.value?.scrollToIndexIfHidden(index)
  }

  return {
    rows,
    selectedItems,
    onRowClick,
    onArrowKey,
    selectAllWithKeyboard,
    selectRowForContextMenu,
  }
}
