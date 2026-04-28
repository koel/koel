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
    <Popover ref="popover" :anchor="button" placement="bottom-end" class="context-menu normal-case tracking-normal">
      <menu>
        <li
          v-for="item in items"
          :key="item.label"
          :class="isCurrentField(item.field) && 'active'"
          :title="`Sort by ${item.label}`"
          class="cursor-pointer group flex justify-between hover:bg-k-highlight hover:text-k-highlight-fg"
          @click="sort(item.field)"
        >
          <span>{{ item.label }}</span>
          <span v-if="isCurrentField(item.field)" class="text-k-fg group-hover:text-k-highlight-fg">
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
import { computed, ref, toRefs } from 'vue'

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
</script>
