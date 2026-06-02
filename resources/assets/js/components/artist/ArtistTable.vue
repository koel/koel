<template>
  <div class="artist-table-wrap relative flex flex-col flex-1 overflow-auto" data-testid="artist-table">
    <div class="artist-table-header sortable flex z-2 bg-k-fg-3 pl-5 sticky top-0">
      <span
        class="name"
        role="button"
        tabindex="0"
        title="Sort by name"
        @click="onSort('name')"
        @keydown.enter.space.prevent="onSort('name')"
      >
        Name
        <Icon v-if="field === 'name' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'name' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('rating')"
        class="rating"
        role="button"
        tabindex="0"
        title="Sort by rating"
        @click="onSort('rating')"
        @keydown.enter.space.prevent="onSort('rating')"
      >
        Rating
        <Icon v-if="field === 'rating' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'rating' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span
        v-if="shouldShowColumn('favorite')"
        class="favorite"
        role="button"
        tabindex="0"
        title="Sort by favorite"
        @click="onSort('favorite')"
        @keydown.enter.space.prevent="onSort('favorite')"
      >
        <Icon :icon="faHeart" />
        <Icon v-if="field === 'favorite' && order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
        <Icon v-if="field === 'favorite' && order === 'desc'" :icon="faCaretDown" class="ml-2 text-k-highlight" />
      </span>
      <span class="extra">
        <ArtistTableHeaderActionMenu :field :order @sort="onSort" />
      </span>
    </div>

    <VirtualScroller :items="artists" :item-height="64" @scrolled-to-end="$emit('scrolled-to-end')">
      <template #default="{ item }: { item: Artist }">
        <ArtistRow :artist="item" @toggle-favorite="emit('toggle-favorite', $event)" />
      </template>
    </VirtualScroller>
  </div>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp, faHeart } from '@fortawesome/free-solid-svg-icons'
import { toRefs } from 'vue'
import { useTableColumnVisibility } from '@/composables/useTableColumnVisibility'
import { artistTableColumnConfig } from '@/config/tables'

import VirtualScroller from '@/components/ui/VirtualScroller.vue'
import ArtistRow from '@/components/artist/ArtistRow.vue'
import ArtistTableHeaderActionMenu from '@/components/artist/ArtistTableHeaderActionMenu.vue'

const props = defineProps<{
  artists: Artist[]
  field: ArtistListSortField
  order: SortOrder
}>()

const emit = defineEmits<{
  (e: 'sort', field: ArtistListSortField, order: SortOrder): void
  (e: 'toggle-favorite', artist: Artist): void
  (e: 'scrolled-to-end'): void
}>()

const { shouldShowColumn } = useTableColumnVisibility(artistTableColumnConfig)
const { field, order } = toRefs(props)

const onSort = (clicked: ArtistListSortField) => {
  const nextOrder: SortOrder = field.value === clicked && order.value === 'asc' ? 'desc' : 'asc'
  emit('sort', clicked, nextOrder)
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.artist-table-wrap {
  .artist-table-header > span {
    @apply text-left p-2 align-middle truncate;

    &.name {
      @apply flex-1 min-w-0 flex items-center;
    }

    &.rating {
      @apply basis-32 flex items-center;
    }

    &.favorite {
      @apply basis-16 text-center;
    }

    &.extra {
      @apply basis-12 text-center;
    }
  }

  .artist-table-header {
    @apply tracking-widest uppercase cursor-pointer text-k-fg-70;

    .extra {
      @apply px-0;
    }
  }
}
</style>
