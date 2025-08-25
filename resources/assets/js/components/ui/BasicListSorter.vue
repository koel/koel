<template>
  <article>
    <button
      ref="button"
      :title="title"
      class="border px-3 rounded-md h-full border-white/10 w-full focus:text-k-highlight text-k-text-secondary active:text-white focus:text-white"
      @click.stop="triggerDropdown"
    >
      <span class="mr-2">{{ currentLabel }}</span>
      <Icon :icon="order === 'asc' ? faArrowUp : faArrowDown" />
    </button>
    <OnClickOutside @trigger="hideDropdown">
      <menu ref="menu" class="context-menu normal-case tracking-normal">
        <li
          v-for="item in items"
          :key="item.label"
          :class="isCurrentField(item.field) && 'active'"
          :title="`Sort by ${item.label}`"
          class="cursor-pointer flex justify-between"
          @click="sort(item.field)"
        >
          <span>{{ item.label }}</span>
          <span v-if="isCurrentField(item.field)" class="opacity-80">
            <Icon v-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </OnClickOutside>
  </article>
</template>

<script generic="T extends SortField" lang="ts" setup>
import { faArrowDown, faArrowUp } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useFloatingUi } from '@/composables/useFloatingUi'

const props = defineProps<{
  items: BasicListSorterDropDownItem<T>[]
  field?: T
  order?: SortOrder
}>()

const emit = defineEmits<{ (e: 'sort', field: T, order: SortOrder): void }>()

const { field: currentField, order: currentOrder, items } = toRefs(props)

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const {
  setup: setupDropdown,
  teardown: teardownDropdown,
  trigger: triggerDropdown,
  hide: hideDropdown,
} = useFloatingUi(button, menu, {
  placement: 'bottom-end',
  useArrow: false,
  autoTrigger: false,
})

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

  hideDropdown()
}

const isCurrentField = (field: T) => field === currentField.value

const title = computed(
  () => `Sorting by ${currentLabel.value}, ${currentOrder.value === 'asc' ? 'ascending' : 'descending'}`,
)

onMounted(() => menu.value && setupDropdown())
onBeforeUnmount(() => teardownDropdown())
</script>
