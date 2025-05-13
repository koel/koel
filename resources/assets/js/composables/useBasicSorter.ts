import type { Ref } from 'vue'
import { computed, getCurrentInstance } from 'vue'
import { useFloatingUi } from '@/composables/useFloatingUi'

export const useBasicSorter = <T extends PodcastListSortField | AlbumListSortField>(
  items: { label: string, field: T }[],
  currentField: Ref<T>,
  currentOrder: Ref<SortOrder>,
  buttonElement: Ref<HTMLElement | undefined>,
  menuElement: Ref<HTMLElement | undefined>,
) => {
  const { emit } = getCurrentInstance()!

  const { setup: setupDropdown, teardown, trigger: triggerDropdown, hide: hideDropdown } = useFloatingUi(
    buttonElement,
    menuElement,
    {
      placement: 'bottom-end',
      useArrow: false,
      autoTrigger: false,
    },
  )

  const currentLabel = computed(() => items.find(item => item.field === currentField.value)?.label)

  const sort = (field: T) => {
    if (field === currentField.value) {
      // if clicking the same field, toggle the order
      emit('sort', field, currentOrder.value === 'asc' ? 'desc' : 'asc')
    } else {
      // otherwise, we do ascending order by default
      emit('sort', field, 'asc')
    }

    hideDropdown()
  }

  const isCurrentField = (field: T) => field === currentField.value

  const title = computed(
    () => `Sorting by ${currentLabel.value}, ${currentOrder.value === 'asc' ? 'ascending' : 'descending'}`,
  )

  return {
    setup: () => menuElement.value && setupDropdown(),
    teardown,
    triggerDropdown,
    hideDropdown,
    sort,
    isCurrentField,
    currentLabel,
    title,
  }
}
