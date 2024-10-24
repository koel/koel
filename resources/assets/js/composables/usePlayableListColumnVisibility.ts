import { ref } from 'vue'
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { logger } from '@/utils/logger'

const visibleColumns: Ref<PlayableListColumnName[]> = ref([])

export const usePlayableListColumnVisibility = () => {
  const collectVisibleColumns = () => {
    const validColumns: PlayableListColumnName[] = ['track', 'title', 'artist', 'album', 'duration', 'play_count']
    const defaultColumns: PlayableListColumnName[] = ['track', 'title', 'artist', 'album', 'duration']

    try {
      let columns = useLocalStorage().get<PlayableListColumnName[]>('playable-list-columns', defaultColumns)!

      columns = columns.filter(column => validColumns.includes(column))

      // Ensure 'title' is always visible
      columns.push('title')
      return Array.from(new Set(columns))
    } catch (error: unknown) {
      process.env.NODE_ENV !== 'test' && logger.error('Failed to load columns from local storage', error)
      return defaultColumns
    }
  }

  if (!visibleColumns.value.length) {
    visibleColumns.value = collectVisibleColumns()
  }

  const isConfigurable = () => {
    const breakpoints = useBreakpoints(breakpointsTailwind)
    return breakpoints.isGreaterOrEqual('md')
  }

  const shouldShowColumn = (name: PlayableListColumnName) => {
    if (!isConfigurable()) {
      // on a smaller screen we render the columns nonetheless and let CSS handles their visibility
      return true
    }

    return visibleColumns.value.includes(name)
  }

  const toggleColumn = (column: PlayableListColumnName) => {
    let columns = visibleColumns.value

    if (!columns.includes(column)) {
      columns.push(column)
    } else {
      columns = columns.filter(c => c !== column)
    }

    visibleColumns.value = columns
    useLocalStorage().set('playable-list-columns', columns)
  }

  return {
    shouldShowColumn,
    toggleColumn,
    isConfigurable,
  }
}
