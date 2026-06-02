<template>
  <article>
    <button ref="button" class="w-full focus:text-k-highlight" title="Sort">
      <Icon :icon="faSort" />
    </button>
    <Popover ref="popover" :anchor="button" placement="bottom-end" class="context-menu normal-case tracking-normal">
      <menu>
        <li
          v-for="item in menuItems"
          :key="item.label"
          :class="field === item.field && 'active'"
          class="cursor-pointer group flex justify-between pl-3! hover:bg-k-highlight! hover:text-k-highlight-fg!"
          @click="sort(item.field)"
        >
          <label class="w-4 mr-2.5 flex items-center" @click.stop="toggle(item.column)">
            <input
              :checked="shouldShowColumn(item.column)"
              :disabled="!isToggleable(item.column)"
              :title="isToggleable(item.column) ? `Click to toggle the ${item.label} column` : ''"
              class="disabled:opacity-20 disabled:cursor-not-allowed bg-k-fg group-hover:border-k-highlight-fg h-4 aspect-square rounded-sm checked:border-k-fg-70 checked:border-2 checked:bg-k-highlight"
              type="checkbox"
            />
          </label>
          <span class="flex-1 text-left">{{ item.label }}</span>
          <span class="icon hidden ml-3">
            <Icon v-if="order === 'asc'" :icon="faArrowUp" />
            <Icon v-else :icon="faArrowDown" />
          </span>
        </li>
      </menu>
    </Popover>
  </article>
</template>

<script lang="ts" setup>
import { faArrowDown, faArrowUp, faSort } from '@fortawesome/free-solid-svg-icons'
import { computed, ref } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { albumTableColumnConfig } from '@/config/tables'

import Popover from '@/components/ui/Popover.vue'

defineProps<{
  field: AlbumListSortField
  order: SortOrder
}>()

const emit = defineEmits<{ (e: 'sort', field: AlbumListSortField): void }>()

interface MenuItem {
  column: AlbumTableColumnName
  label: string
  field: AlbumListSortField
}

const { shouldShowColumn, toggleColumn, isToggleable } = useTableColumnVisibility(albumTableColumnConfig)

const button = ref<HTMLButtonElement>()
const popover = ref<InstanceType<typeof Popover>>()

const menuItems = computed<MenuItem[]>(() => [
  { column: 'name', label: 'Name', field: 'name' },
  { column: 'artist', label: 'Artist', field: 'artist_name' },
  { column: 'time', label: 'Time', field: 'length' },
  { column: 'year', label: 'Year', field: 'year' },
  { column: 'rating', label: 'Rating', field: 'rating' },
  { column: 'favorite', label: 'Favorite', field: 'favorite' },
])

const sort = (field: AlbumListSortField) => {
  emit('sort', field)
  popover.value?.hide()
}

const toggle = (column: AlbumTableColumnName) => {
  if (!isToggleable(column)) {
    return
  }

  toggleColumn(column)
  popover.value?.hide()
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.active {
  @apply bg-k-highlight text-k-highlight-fg;

  .icon {
    @apply block;
  }

  input {
    @apply border-k-highlight-fg!;
  }
}
</style>
