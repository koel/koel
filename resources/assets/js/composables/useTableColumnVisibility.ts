import type { Ref } from 'vue'
import { ref } from 'vue'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { logger } from '@/utils/logger'

interface Options<T extends string> {
  storageKey: string
  validColumns: readonly T[]
  defaultColumns: readonly T[]
  alwaysVisible: readonly T[]
  /**
   * When true, the column-visibility preference only applies at md+ breakpoints.
   * Below md, all columns report visible (the caller is expected to hide them
   * via CSS instead). Defaults to false.
   */
  responsive?: boolean
}

const stores: Record<string, Ref<string[]>> = {}

export const useTableColumnVisibility = <T extends string>({
  storageKey,
  validColumns,
  defaultColumns,
  alwaysVisible,
  responsive = false,
}: Options<T>) => {
  if (!stores[storageKey]) {
    stores[storageKey] = ref([])
  }

  const visibleColumns = stores[storageKey] as Ref<T[]>

  const collectVisibleColumns = (): T[] => {
    try {
      const stored = useLocalStorage().get<T[]>(storageKey, [...defaultColumns])!
      const filtered = stored.filter(column => validColumns.includes(column))
      const merged = new Set([...filtered, ...alwaysVisible])
      return Array.from(merged)
    } catch (error: unknown) {
      window.RUNNING_UNIT_TESTS || logger.error(`Failed to load columns for ${storageKey}`, error)
      return Array.from(new Set([...defaultColumns, ...alwaysVisible]))
    }
  }

  if (!visibleColumns.value.length) {
    visibleColumns.value = collectVisibleColumns()
  }

  const isConfigurable = () => {
    if (!responsive) {
      return true
    }

    return useBreakpoints(breakpointsTailwind).isGreaterOrEqual('md')
  }

  const shouldShowColumn = (name: T) => {
    if (!isConfigurable()) {
      return true
    }

    return visibleColumns.value.includes(name)
  }

  const toggleColumn = (column: T) => {
    if (alwaysVisible.includes(column)) {
      return
    }

    let next = visibleColumns.value

    if (next.includes(column)) {
      next = next.filter(c => c !== column)
    } else {
      next = [...next, column]
    }

    visibleColumns.value = next

    try {
      useLocalStorage().set(storageKey, next)
    } catch (error: unknown) {
      window.RUNNING_UNIT_TESTS || logger.error(`Failed to persist columns for ${storageKey}`, error)
    }
  }

  const isToggleable = (column: T) => !alwaysVisible.includes(column)

  return {
    shouldShowColumn,
    toggleColumn,
    isToggleable,
    isConfigurable,
  }
}
