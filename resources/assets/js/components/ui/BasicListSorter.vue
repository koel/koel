<template>
  <article>
    <button
      ref="button"
      :title="title"
      class="border px-3 rounded-md h-full border-k-fg-10 w-full focus:text-k-fg hover:text-k-fg active:text-k-fg"
    >
      <span class="mr-2">{{ currentLabel }}</span>
      <Icon :icon="order === 'asc' ? faArrowUp : faArrowDown" />
    </button>
    <Popover
      ref="popover"
      :anchor="button"
      placement="bottom-end"
      class="context-menu normal-case tracking-normal"
      @toggle="onPopoverToggle"
    >
      <menu role="menu" aria-orientation="vertical" @keydown="onKeydown">
        <li
          v-for="(item, index) in items"
          :key="item.label"
          :ref="el => setItemRef(el, index)"
          :class="isCurrentField(item.field) && 'active'"
          :title="`Sort by ${item.label}`"
          :tabindex="focusedIndex === index ? 0 : -1"
          role="menuitem"
          class="cursor-pointer group flex justify-between outline-none hover:bg-k-highlight hover:text-k-highlight-fg focus:bg-k-highlight focus:text-k-highlight-fg"
          @click="sort(item.field)"
        >
          <span>{{ item.label }}</span>
          <span
            v-if="isCurrentField(item.field)"
            class="text-k-fg group-hover:text-k-highlight-fg group-focus:text-k-highlight-fg"
          >
            <Icon v-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </Popover>
  </article>
</template>

<script generic="T extends SortField" lang="ts" setup>
import { faArrowDown, faArrowUp } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, ref, toRefs } from 'vue'

import Popover from '@/components/ui/Popover.vue'

const props = defineProps<{
  items: BasicListSorterDropDownItem<T>[]
  field?: T
  order?: SortOrder
}>()

const emit = defineEmits<{ (e: 'sort', field: T, order: SortOrder): void }>()

const { field: currentField, order: currentOrder, items } = toRefs(props)

const button = ref<HTMLButtonElement>()
const popover = ref<InstanceType<typeof Popover>>()
const itemRefs = ref<HTMLLIElement[]>([])
const focusedIndex = ref(0)

const setItemRef = (el: unknown, index: number) => {
  if (el instanceof HTMLLIElement) {
    itemRefs.value[index] = el
  }
}

const currentLabel = computed(() => {
  return items.value.find((item: BasicListSorterDropDownItem<T>) => item.field === currentField.value)?.label
})

const sort = (field: T) => {
  if (field === currentField.value) {
    // if clicking the same field, toggle the order
    emit('sort', field, currentOrder.value === 'asc' ? 'desc' : 'asc')
  } else {
    // otherwise, we do ascending order by default
    emit('sort', field, 'asc')
  }

  popover.value?.hide()
}

const isCurrentField = (field: T) => field === currentField.value

const title = computed(
  () => `Sorting by ${currentLabel.value}, ${currentOrder.value === 'asc' ? 'ascending' : 'descending'}`,
)

const focusItem = async (index: number) => {
  focusedIndex.value = index
  // Wait for the tabindex update to apply before moving focus.
  await nextTick()
  itemRefs.value[index]?.focus()
}

const onPopoverToggle = (open: boolean) => {
  if (!open) {
    return
  }
  // On open, focus the currently-active sort option, falling back to the first item.
  const activeIndex = items.value.findIndex(item => item.field === currentField.value)
  focusItem(activeIndex >= 0 ? activeIndex : 0)
}

const onKeydown = (event: KeyboardEvent) => {
  const len = items.value.length

  if (!len) {
    return
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      focusItem((focusedIndex.value + 1) % len)
      break

    case 'ArrowUp':
      event.preventDefault()
      focusItem((focusedIndex.value - 1 + len) % len)
      break

    case 'Home':
      event.preventDefault()
      focusItem(0)
      break

    case 'End':
      event.preventDefault()
      focusItem(len - 1)
      break

    case 'Enter':
    case ' ': {
      event.preventDefault()
      const item = items.value[focusedIndex.value]
      if (item) {
        sort(item.field)
      }
      break
    }
  }
}
</script>
