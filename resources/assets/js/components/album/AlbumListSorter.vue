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
            <Icon v-if="order === 'asc'" class="" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </OnClickOutside>
  </article>
</template>

<script setup lang="ts">
import { faArrowDown, faArrowUp } from '@fortawesome/free-solid-svg-icons'
import { OnClickOutside } from '@vueuse/components'
import { onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useBasicSorter } from '@/composables/useBasicSorter'

const props = withDefaults(defineProps<{
  field?: AlbumListSortField
  order?: SortOrder
}>(), {
  field: 'name',
  order: 'asc',
})

const { field, order } = toRefs(props)

const button = ref<HTMLButtonElement>()
const menu = ref<HTMLDivElement>()

const items: { label: string, field: AlbumListSortField }[] = [
  { label: 'Name', field: 'name' },
  { label: 'Artist', field: 'artist_name' },
  { label: 'Release Year', field: 'year' },
  { label: 'Date Added', field: 'created_at' },
]

const {
  setup,
  teardown,
  triggerDropdown,
  hideDropdown,
  currentLabel,
  sort,
  isCurrentField,
  title,
} = useBasicSorter<AlbumListSortField>(items, field, order, button, menu)

onMounted(() => setup())
onBeforeUnmount(() => teardown())
</script>
